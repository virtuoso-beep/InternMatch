<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Competency;
use App\Models\Evaluation;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProgramAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_analytics_uses_scoped_records_unique_enrollments_and_submitted_scores_without_student_identity(): void
    {
        $own = StudentEnrollment::factory()->create();
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['student_enrollment_id' => $own->id, 'status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => today()]);
        $placement->opportunity->programs()->attach($own->programTerm->program_id, ['capacity' => 3]);
        $placement->opportunity->update(['status' => 'published']);
        $competency = Competency::factory()->create();
        $placement->opportunity->competencies()->attach($competency->id);
        $placement->moa->programs()->attach($own->programTerm->program_id);
        $placement->moa->update(['status' => 'active', 'effective_on' => today()->subDay(), 'expires_on' => today()->addDays(30)]);
        $placement->hostEstablishment->update(['latitude' => 0, 'longitude' => 0]);
        $own->student->user->profile()->create(['latitude' => 0, 'longitude' => .01]);
        Placement::factory()->create(['student_enrollment_id' => $own->id, 'status' => 'completed', 'supervisor_id' => $supervisor->id, 'starts_on' => today(), 'completed_at' => now()]);
        $outside = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => today()]);
        $evaluation = Evaluation::factory()->create(['placement_id' => $placement->id, 'status' => 'submitted', 'submitted_at' => now()]);
        $criterion = EvaluationCriterion::factory()->create(['evaluation_rubric_id' => $evaluation->evaluation_rubric_id, 'max_score' => 5, 'weight' => 1]);
        EvaluationScore::factory()->create(['evaluation_id' => $evaluation->id, 'evaluation_rubric_id' => $evaluation->evaluation_rubric_id, 'evaluation_criterion_id' => $criterion->id, 'score' => 4]);
        Evaluation::factory()->create(['placement_id' => $placement->id, 'period' => 'final', 'status' => 'draft']);
        foreach ([Role::Dean, Role::Coordinator] as $role) {
            $actor = User::factory()->withRole($role)->create();
            $actor->programs()->attach($own->programTerm->program_id);
            $response = $this->actingAs($actor)->getJson('/api/v1/program-analytics')->assertOk()
                ->assertJsonCount(1, 'programs')->assertJsonPath('programs.0.enrollments', 1)
                ->assertJsonPath('programs.0.placed', 1)->assertJsonPath('programs.0.completed', 1)
                ->assertJsonPath('programs.0.evaluation_count', 1)->assertJsonPath('equity.measured_placements', 1)
                ->assertJsonPath('equity.missing_coordinates', 0)->assertJsonPath('hosts.0.current_agreements', 1)
                ->assertJsonPath('hosts.0.agreements_expiring_90_days', 1)->assertJsonPath('competency_demand.0.value', 1);
            $this->assertEquals(80, $response->json('programs.0.evaluation_percent'));
            $this->assertEquals(100, $response->json('programs.0.placement_rate'));
            $this->assertEqualsWithDelta(1.11, $response->json('equity.median_km'), .01);
            $this->assertStringNotContainsString($outside->hostEstablishment->name, $response->getContent());
            $this->assertStringNotContainsString($own->student->user->name, $response->getContent());
            $this->assertStringNotContainsString($own->student->user->email, $response->getContent());
        }
    }

    public function test_retired_student_account_counts_as_missing_coordinates_instead_of_failing(): void
    {
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id, 'starts_on' => today()]);
        $placement->studentEnrollment->student->user->delete();
        $actor = User::factory()->withRole(Role::Dean)->create();
        $actor->programs()->attach($placement->studentEnrollment->programTerm->program_id);

        $this->actingAs($actor)->getJson('/api/v1/program-analytics')->assertOk()
            ->assertJsonPath('equity.missing_coordinates', 1)->assertJsonPath('equity.median_km', null);
    }

    public function test_empty_scopes_and_missing_coordinates_are_not_invented_zero_distances(): void
    {
        $actor = User::factory()->withRole(Role::Dean)->create();
        $this->actingAs($actor)->getJson('/api/v1/program-analytics')->assertOk()->assertJsonCount(0, 'programs')->assertJsonCount(0, 'hosts')->assertJsonPath('equity.median_km', null);
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id, 'starts_on' => today()]);
        $actor->programs()->attach($placement->studentEnrollment->programTerm->program_id);
        $this->getJson('/api/v1/program-analytics')->assertOk()->assertJsonPath('equity.missing_coordinates', 1)->assertJsonPath('equity.median_km', null)->assertJsonPath('programs.0.evaluation_percent', null);
        foreach ([Role::Student, Role::Supervisor, Role::Admin] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/program-analytics')->assertForbidden();
        }
    }

    public function test_weekly_activity_covers_sixteen_weeks_and_similarity_bands_are_raw_scores(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(9, 0)); // a Wednesday; week starts Monday 16 Nov
        $enrollment = StudentEnrollment::factory()->create();
        $placement = Placement::factory()->create(['student_enrollment_id' => $enrollment->id, 'status' => 'active', 'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id, 'starts_on' => '2026-09-01']);
        $log = fn (string $date, string $status, ?int $minutes) => TimeLog::factory()->create(['placement_id' => $placement->id, 'work_date' => $date, 'time_in' => "{$date} 08:00:00", 'time_out' => "{$date} 17:00:00", 'status' => $status, 'credited_minutes' => $minutes]
            + ($status === 'verified' ? ['verified_by' => $placement->supervisor_id, 'verified_at' => "{$date} 18:00:00"] : []));
        $log('2026-11-16', 'verified', 480);
        $log('2026-11-17', 'pending', null);
        $log('2026-11-10', 'verified', 420);
        $log('2026-06-01', 'verified', 480); // outside the 16-week window
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($enrollment->programTerm->program_id);

        $weeks = collect($this->actingAs($coordinator)->getJson('/api/v1/program-analytics')->assertOk()->json('weekly'));
        $this->assertCount(16, $weeks);
        $this->assertSame('2026-11-16', $weeks->last()['week']);
        $this->assertEquals(['certified_hours' => 8.0, 'logs' => 2, 'interns' => 1], collect($weeks->last())->only(['certified_hours', 'logs', 'interns'])->all());
        $this->assertEquals(7.0, $weeks->firstWhere('week', '2026-11-09')['certified_hours']);
        $this->assertEquals(15.0, $weeks->sum('certified_hours'));

        $labels = collect($this->getJson('/api/v1/program-analytics')->json('similarity_bands'))->pluck('label');
        $this->assertSame(['0.90 to 1.00', '0.80 to under 0.90', '0.70 to under 0.80', 'Below 0.70'], $labels->all());
    }
}
