<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementType;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_coordinator_tiles_count_awaiting_incomplete_requirements_and_open_flags_only_in_scope(): void
    {
        $a = StudentEnrollment::factory()->create();
        $b = StudentEnrollment::factory()->create(['program_term_id' => $a->program_term_id]);
        $placed = StudentEnrollment::factory()->create(['program_term_id' => $a->program_term_id]);
        $outside = StudentEnrollment::factory()->create();
        ProgramTermRequirement::factory()->create(['program_term_id' => $a->program_term_id, 'is_required' => true, 'required_before_deployment' => true,
            'requirement_type_id' => RequirementType::factory()->create(['kind' => 'document'])->id]);
        $supervisor = User::factory()->withRole(Role::Supervisor)->create()->id;
        $placement = Placement::factory()->create(['student_enrollment_id' => $placed->id, 'status' => 'active', 'supervisor_id' => $supervisor, 'starts_on' => today()]);
        $flag = ['placement_id' => $placement->id, 'code' => 'hours', 'severity' => 'warning', 'description' => 'x', 'raised_at' => now(), 'created_at' => now(), 'updated_at' => now()];
        DB::table('monitoring_flags')->insert($flag);
        DB::table('monitoring_flags')->insert($flag + ['resolved_at' => now(), 'resolved_by' => $supervisor, 'resolution' => 'done']);
        $outsidePlacement = Placement::factory()->create(['student_enrollment_id' => $outside->id, 'status' => 'active', 'supervisor_id' => $supervisor, 'starts_on' => today()]);
        DB::table('monitoring_flags')->insert(array_merge($flag, ['placement_id' => $outsidePlacement->id]));
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($a->programTerm->program_id);

        $stats = collect($this->actingAs($coordinator)->getJson('/api/v1/dashboard')->assertOk()->json('stats'))->pluck('value', 'label');

        $this->assertSame(2, $stats['Students awaiting placement']);
        $this->assertSame(2, $stats['Awaiting students with incomplete requirements']);
        $this->assertSame(1, $stats['Students needing attention (open monitoring flags)']);
        $this->assertIsInt($stats['Available placement slots']);
        $dean = User::factory()->withRole(Role::Dean)->create();
        $dean->programs()->attach($a->programTerm->program_id);
        $this->assertArrayNotHasKey('Students awaiting placement', collect($this->actingAs($dean)->getJson('/api/v1/dashboard')->json('stats'))->pluck('value', 'label'));
    }
}
