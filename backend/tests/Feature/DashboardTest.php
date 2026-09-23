<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_program_summary_is_scoped_and_excludes_unverified_hours(): void
    {
        $own = StudentEnrollment::factory()->create();
        $other = StudentEnrollment::factory()->create();
        $placement = Placement::factory()->create(['student_enrollment_id' => $own->id]);
        TimeLog::factory()->create(['placement_id' => $placement->id, 'status' => 'verified', 'credited_minutes' => 480, 'verified_by' => User::factory()->withRole(Role::Supervisor)->create()->id, 'verified_at' => now()]);
        TimeLog::factory()->create(['placement_id' => $placement->id, 'time_in' => now()->subDays(2)->setTime(8, 0), 'time_out' => now()->subDays(2)->setTime(17, 0), 'status' => 'pending', 'credited_minutes' => null]);
        foreach ([Role::Coordinator, Role::Dean] as $role) {
            $user = User::factory()->withRole($role)->create();
            $user->programs()->attach($own->programTerm->program_id);
            $response = $this->actingAs($user)->getJson('/api/v1/dashboard')->assertOk()->assertJsonCount(1, 'programs')->assertJsonCount(1, 'program_breakdown');
            $stats = collect($response->json('stats'))->pluck('value', 'label');
            $this->assertEquals(1, $stats['Enrollment records']);
            $this->assertEquals(8, $stats['Certified hours']);
            $this->assertStringNotContainsString($own->student->user->name, $response->getContent());
            $this->getJson('/api/v1/dashboard?program_id='.$other->programTerm->program_id)->assertForbidden();
            $this->getJson('/api/v1/dashboard?program_id='.$own->programTerm->program_id)->assertOk()->assertJsonPath('program_breakdown.0.enrollments', 1);
        }
    }

    public function test_supervisor_summary_requires_assignment_to_both_intern_and_host(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $own = Placement::factory()->create(['supervisor_id' => $supervisor->id]);
        $supervisor->hostEstablishments()->attach($own->host_establishment_id);
        Placement::factory()->create(['host_establishment_id' => $own->host_establishment_id, 'opportunity_id' => $own->opportunity_id,
            'student_enrollment_id' => StudentEnrollment::factory()->create(['program_term_id' => $own->studentEnrollment->program_term_id])->id]);
        $response = $this->actingAs($supervisor)->getJson('/api/v1/dashboard')->assertOk();
        $this->assertEquals(1, collect($response->json('stats'))->pluck('value', 'label')['Enrollment records']);
        $supervisor->hostEstablishments()->detach();
        $response = $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonCount(0, 'program_breakdown');
        $this->assertEquals(0, collect($response->json('stats'))->pluck('value', 'label')['Enrollment records']);
    }
}
