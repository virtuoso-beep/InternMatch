<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Competency;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\StudentCompetency;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\OpportunityEligibility;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function eligible(): array
    {
        $this->travelTo(now()->setDate(2026, 9, 19));
        $enrollment = StudentEnrollment::factory()->create();
        $opportunity = Opportunity::factory()->create(['academic_term_id' => $enrollment->programTerm->academic_term_id]);
        $opportunity->programs()->attach($enrollment->programTerm->program_id, ['capacity' => 2]);
        DB::table('host_program_capacity')->insert([
            'host_establishment_id' => $opportunity->host_establishment_id,
            'academic_term_id' => $opportunity->academic_term_id,
            'program_id' => $enrollment->programTerm->program_id, 'capacity' => 2,
        ]);
        $moa = Moa::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id]);
        $moa->programs()->attach($enrollment->programTerm->program_id);

        return [$enrollment, $opportunity, $moa];
    }

    public function test_eligibility_requires_program_capacity_and_valid_program_coverage_but_not_paperwork(): void
    {
        [$student, $opportunity, $moa] = $this->eligible();
        $service = app(OpportunityEligibility::class);
        $this->assertNotNull($service->inspect($student, $opportunity));
        $this->assertSame(0, $student->requirementSubmissions()->count());
        $this->actingAs($student->student->user)->getJson('/api/v1/enrollments/'.$student->id.'/opportunities')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.eligibility.capacity_remaining', 2);
        $moa->programs()->detach();
        $this->assertNull($service->inspect($student, $opportunity));
        $moa->update(['is_institution_wide' => true]);
        $this->assertNotNull($service->inspect($student, $opportunity));
        $moa->update(['expires_on' => '2026-09-18']);
        $this->assertNull($service->inspect($student, $opportunity));
    }

    public function test_moa_start_and_expiry_are_inclusive(): void
    {
        [$student, $opportunity, $moa] = $this->eligible();
        $moa->update(['effective_on' => '2026-09-19', 'expires_on' => '2026-09-19']);
        $this->assertNotNull(app(OpportunityEligibility::class)->inspect($student, $opportunity));
        $moa->update(['effective_on' => '2026-09-20', 'expires_on' => '2026-10-01']);
        $this->assertNull(app(OpportunityEligibility::class)->inspect($student, $opportunity));
    }

    public function test_shared_capacity_cannot_substitute_for_program_or_host_capacity(): void
    {
        [$student, $opportunity] = $this->eligible();
        $service = app(OpportunityEligibility::class);
        $opportunity->programs()->updateExistingPivot($student->programTerm->program_id, ['capacity' => null]);
        $this->assertNull($service->inspect($student, $opportunity));
        $opportunity->programs()->updateExistingPivot($student->programTerm->program_id, ['capacity' => 2]);
        DB::table('host_program_capacity')->delete();
        $this->assertNull($service->inspect($student, $opportunity));
    }

    public function test_occupied_host_capacity_blocks_other_opportunities_in_same_program(): void
    {
        [$student, $opportunity, $moa] = $this->eligible();
        DB::table('host_program_capacity')->update(['capacity' => 1]);
        $another = Opportunity::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id, 'academic_term_id' => $opportunity->academic_term_id]);
        $intern = StudentEnrollment::factory()->create(['program_term_id' => $student->program_term_id]);
        Placement::factory()->create(['student_enrollment_id' => $intern->id, 'host_establishment_id' => $opportunity->host_establishment_id, 'opportunity_id' => $another->id, 'moa_id' => $moa->id]);
        $this->assertNull(app(OpportunityEligibility::class)->inspect($student, $opportunity));
    }

    public function test_wrong_program_and_closed_opportunities_are_excluded(): void
    {
        [$student, $opportunity] = $this->eligible();
        $service = app(OpportunityEligibility::class);
        $opportunity->update(['status' => 'closed']);
        $this->assertNull($service->inspect($student, $opportunity));
        $opportunity->update(['status' => 'published']);
        $opportunity->programs()->detach();
        $opportunity->programs()->attach(Program::factory()->create(), ['capacity' => 10]);
        $this->assertNull($service->inspect($student, $opportunity));
    }

    public function test_student_and_coordinator_queries_cannot_retrieve_other_program_students(): void
    {
        [$own] = $this->eligible();
        $other = StudentEnrollment::factory()->create();
        $this->actingAs($own->student->user)->getJson('/api/v1/enrollments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson('/api/v1/enrollments/'.$other->id.'/opportunities')->assertNotFound();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($own->programTerm->program_id);
        $this->actingAs($coordinator)->getJson('/api/v1/enrollments')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/enrollments/'.$other->id.'/opportunities')->assertNotFound();
    }

    public function test_competencies_persist_update_and_delete_only_for_the_owner(): void
    {
        $student = StudentEnrollment::factory()->create();
        $competency = Competency::factory()->create();
        $student->programTerm->program->competencies()->attach($competency);
        $this->actingAs($student->student->user)->getJson('/api/v1/competencies')->assertOk()->assertJsonCount(1, 'vocabulary');
        $this->postJson('/api/v1/competencies', ['competency_id' => $competency->id, 'level' => 70])->assertOk();
        $this->postJson('/api/v1/competencies', ['competency_id' => $competency->id, 'level' => 80])->assertOk();
        $this->assertDatabaseCount('student_competencies', 1);
        $this->getJson('/api/v1/competencies')->assertJsonPath('data.0.level', 80);
        $record = $student->student->studentCompetencies()->firstOrFail();
        $other = StudentCompetency::factory()->create();
        $this->deleteJson('/api/v1/competencies/'.$other->id)->assertNotFound();
        $this->deleteJson('/api/v1/competencies/'.$record->id)->assertNoContent();
        $this->assertDatabaseMissing('student_competencies', ['id' => $record->id]);
        $this->assertDatabaseCount('audit_logs', 3);
    }
}
