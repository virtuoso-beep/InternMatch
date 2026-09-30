<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CohortAllocationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fixture(): array
    {
        $enrollment = StudentEnrollment::factory()->create();
        $term = $enrollment->programTerm;
        $term->program->update(['required_ojt_hours' => 486]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($term->program_id);
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $opportunity = Opportunity::factory()->create(['academic_term_id' => $term->academic_term_id]);
        $opportunity->programs()->attach($term->program_id, ['capacity' => 1]);
        $supervisor->hostEstablishments()->attach($opportunity->host_establishment_id);
        DB::table('host_program_capacity')->insert(['host_establishment_id' => $opportunity->host_establishment_id, 'program_id' => $term->program_id, 'academic_term_id' => $term->academic_term_id, 'capacity' => 1]);
        $moa = Moa::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id, 'status' => 'active', 'effective_on' => today()->subDay(), 'expires_on' => today()->addYear()]);
        $moa->programs()->attach($term->program_id);
        $requirement = ProgramTermRequirement::factory()->create(['program_term_id' => $term->id]);
        foreach ([$enrollment, StudentEnrollment::factory()->create(['program_term_id' => $term->id])] as $e) {
            DB::table('student_enrollments')->where('id', $e->id)->update(['academic_eligibility_confirmed' => true, 'eligibility_confirmed_by' => $coordinator->id, 'eligibility_confirmed_at' => now()]);
            RequirementSubmission::factory()->create(['student_enrollment_id' => $e->id, 'program_term_requirement_id' => $requirement->id, 'status' => 'approved']);
            $generation = (string) Str::uuid();
            DB::table('recommendation_generations')->insert(['generation_id' => $generation, 'student_enrollment_id' => $e->id, 'created_by' => $coordinator->id, 'generated_at' => now()]);
            DB::table('recommendations')->insert(['generation_id' => $generation, 'student_enrollment_id' => $e->id, 'opportunity_id' => $opportunity->id, 'similarity_score' => $e->id === $enrollment->id ? 0.9 : 0.8, 'distance_km' => 2, 'capacity_at_time' => 1, 'moa_status_at_time' => 'active', 'rank' => 1, 'ranking_method' => 'cosine_with_placement_criteria', 'snapshot' => '{}', 'generated_at' => now()]);
        }

        return [$enrollment, $coordinator, $supervisor, $opportunity, $requirement];
    }

    public function test_proposal_reserves_capacity_without_finalizing_and_only_scoped_coordinator_can_decide(): void
    {
        [$e, $coordinator, $supervisor] = $this->fixture();
        $url = '/api/v1/program-terms/'.$e->program_term_id.'/allocation';
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson($url)->assertForbidden();
        $id = $this->actingAs($coordinator)->postJson($url)->assertCreated()->json('id');
        $this->assertDatabaseCount('placements', 0);
        $this->assertSame(1, DB::table('placement_proposal_items')->where('placement_proposal_id', $id)->whereNotNull('opportunity_id')->count());
        $item = DB::table('placement_proposal_items')->where('placement_proposal_id', $id)->where('student_enrollment_id', $e->id)->first();
        $decision = ['decision' => 'approve', 'reason' => 'Coordinator reviewed synthetic test eligibility.', 'supervisor_id' => $supervisor->id, 'starts_on' => today()->toDateString(), 'ends_on' => today()->addMonth()->toDateString()];
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create())->postJson('/api/v1/allocation-items/'.$item->id.'/decision', $decision)->assertForbidden();
        $this->actingAs($coordinator)->postJson('/api/v1/allocation-items/'.$item->id.'/decision', $decision)->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson('/api/v1/allocation-items/'.$item->id.'/decision', $decision)->assertConflict();
        $this->assertDatabaseCount('placements', 1);
        $this->assertDatabaseCount('placement_decisions', 1);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_final_decision_rechecks_latest_requirements_capacity_and_agreement(): void
    {
        [$e, $coordinator, $supervisor, $opportunity, $requirement] = $this->fixture();
        $id = $this->actingAs($coordinator)->postJson('/api/v1/program-terms/'.$e->program_term_id.'/allocation')->assertCreated()->json('id');
        $item = DB::table('placement_proposal_items')->where('placement_proposal_id', $id)->where('student_enrollment_id', $e->id)->first();
        $url = '/api/v1/allocation-items/'.$item->id.'/decision';
        $decision = ['decision' => 'approve', 'reason' => 'Synthetic test approval.', 'supervisor_id' => $supervisor->id, 'starts_on' => today()->toDateString(), 'ends_on' => today()->addMonth()->toDateString()];
        $revision = RequirementSubmission::factory()->create(['student_enrollment_id' => $e->id, 'program_term_requirement_id' => $requirement->id, 'revision' => 2, 'status' => 'submitted']);
        $this->postJson($url, $decision)->assertUnprocessable();
        $revision->update(['status' => 'approved']);
        $opportunity->programs()->updateExistingPivot($e->programTerm->program_id, ['capacity' => 0]);
        $this->postJson($url, $decision)->assertUnprocessable();
        $opportunity->programs()->updateExistingPivot($e->programTerm->program_id, ['capacity' => 1]);
        Moa::where('host_establishment_id', $opportunity->host_establishment_id)->update(['expires_on' => today()->addDay()]);
        $this->postJson($url, $decision)->assertUnprocessable();
        $this->assertDatabaseCount('placements', 0);
        $this->postJson($url, ['decision' => 'reject', 'reason' => 'Agreement does not cover placement.'])->assertOk();
        $this->assertDatabaseHas('placement_proposal_items', ['id' => $item->id, 'review_status' => 'rejected']);
    }

    public function test_unconfirmed_academic_eligibility_is_explicitly_unassigned_and_assessment_is_scoped(): void
    {
        [$e, $coordinator] = $this->fixture();
        DB::table('student_enrollments')->where('id', $e->id)->update(['academic_eligibility_confirmed' => null]);
        $this->actingAs($coordinator)->getJson('/api/v1/allocation')->assertOk()->assertJsonPath('students.0.academic_eligibility_confirmed', null);
        $id = $this->postJson('/api/v1/program-terms/'.$e->program_term_id.'/allocation')->assertCreated()->json('id');
        $this->assertDatabaseHas('placement_proposal_items', ['placement_proposal_id' => $id, 'student_enrollment_id' => $e->id, 'opportunity_id' => null]);
        $url = '/api/v1/enrollments/'.$e->id.'/academic-eligibility';
        $this->actingAs($e->student->user)->postJson($url, ['eligible' => true, 'reason' => 'Attempt own approval'])->assertForbidden();
        $this->actingAs($coordinator)->postJson($url, ['eligible' => true, 'reason' => 'Confirmed synthetic academic record.'])->assertNoContent();
        $this->assertDatabaseHas('student_enrollments', ['id' => $e->id, 'academic_eligibility_confirmed' => true, 'eligibility_confirmed_by' => $coordinator->id]);
    }
}
