<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Evaluation;
use App\Models\Placement;
use App\Models\ProgramTermRequirement;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\ProgressSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Spec §13 adopted default rules, tested at their boundaries (FR-14 must be demonstrated). */
class SpecMonitoringRulesTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** Deployment Mon 2 Nov – Fri 11 Dec 2026 = 30 working days; "today" is Wed 18 Nov (12 working days done). */
    private function placement(int $requiredHours = 240): Placement
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00', 'Asia/Manila'));
        $placement = Placement::factory()->create(['status' => 'active', 'starts_on' => '2026-11-02', 'ends_on' => '2026-12-11',
            'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id]);
        $placement->studentEnrollment->programTerm->program->update(['required_ojt_hours' => $requiredHours]);

        return $placement;
    }

    private function certify(Placement $placement, int $minutes, string $date = '2026-11-17'): void
    {
        TimeLog::factory()->create(['placement_id' => $placement->id, 'work_date' => $date, 'time_in' => $date.' 08:00:00', 'time_out' => $date.' 17:00:00',
            'status' => 'verified', 'credited_minutes' => $minutes, 'verified_by' => $placement->supervisor_id, 'verified_at' => now()]);
    }

    private function codes(Placement $placement): array
    {
        return array_column(app(ProgressSummary::class)->forPlacement($placement->fresh())['flags'], 'severity', 'code');
    }

    public function test_working_days_count_monday_to_friday_only(): void
    {
        $this->assertSame(30, ProgressSummary::workingDays(CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-12-12')));
        $this->assertSame(12, ProgressSummary::workingDays(CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-11-18')));
    }

    public function test_rule_1_behind_schedule_triggers_below_85_percent_of_expected_pace(): void
    {
        // Expected by today E = 240 × 12 ÷ 30 = 96 h; 85% of E = 81.6 h.
        $placement = $this->placement();
        $this->certify($placement, 82 * 60);
        $this->assertArrayNotHasKey('behind_schedule', $this->codes($placement));
        DB::table('time_logs')->where('placement_id', $placement->id)->update(['credited_minutes' => 81 * 60]);
        $this->assertSame('warning', $this->codes($placement)['behind_schedule']);
    }

    public function test_rule_1_waits_for_the_first_ten_working_days(): void
    {
        $placement = $this->placement();
        $placement->update(['starts_on' => '2026-11-05', 'ends_on' => '2026-12-11']); // 9 working days done
        $this->certify($placement, 0);
        $this->assertArrayNotHasKey('behind_schedule', $this->codes($placement));
    }

    public function test_rule_2_cannot_finish_is_critical_only_when_remaining_hours_exceed_capacity(): void
    {
        // Working days left from today to 11 Dec = 18; capacity = 18 × 8 = 144 h.
        $placement = $this->placement(240);
        $this->certify($placement, 96 * 60); // 144 h remain: exactly at capacity
        $this->assertArrayNotHasKey('cannot_finish', $this->codes($placement));
        DB::table('time_logs')->where('placement_id', $placement->id)->update(['credited_minutes' => 95 * 60]); // 145 h remain
        $this->assertSame('critical', $this->codes($placement)['cannot_finish']);
    }

    public function test_rule_3_no_hours_logged_after_five_working_days(): void
    {
        $placement = $this->placement();
        $this->certify($placement, 96 * 60, '2026-11-11'); // Wed; Thu–Tue = 4 working days without a log
        $this->assertArrayNotHasKey('no_hours_logged', $this->codes($placement));
        DB::table('time_logs')->where('placement_id', $placement->id)->update(['work_date' => '2026-11-10']); // 5 working days
        $this->assertArrayHasKey('no_hours_logged', $this->codes($placement));
    }

    public function test_rule_4_pending_log_waits_more_than_five_working_days(): void
    {
        $placement = $this->placement();
        $this->certify($placement, 96 * 60);
        $log = TimeLog::factory()->create(['placement_id' => $placement->id, 'work_date' => '2026-11-16', 'time_in' => '2026-11-16 08:00:00', 'time_out' => '2026-11-16 12:00:00', 'status' => 'pending']);
        DB::table('time_logs')->where('id', $log->id)->update(['created_at' => '2026-11-10 09:00:00']); // 5 working days waiting
        $this->assertArrayNotHasKey('hours_not_certified', $this->codes($placement));
        DB::table('time_logs')->where('id', $log->id)->update(['created_at' => '2026-11-09 09:00:00']); // 6 working days
        $this->assertArrayHasKey('hours_not_certified', $this->codes($placement));
    }

    public function test_rule_5_weekly_report_missing_after_three_working_day_grace(): void
    {
        $placement = $this->placement();
        $this->certify($placement, 96 * 60);
        // Week of 2 Nov ended Sun 8 Nov (grace passed); week of 9 Nov ended Sun 15 Nov (Mon–Tue = 2 days, still in grace).
        $this->assertStringContainsString('1 completed week', collect(app(ProgressSummary::class)->forPlacement($placement->fresh())['flags'])->firstWhere('code', 'weekly_report_missing')['description']);
        DB::table('journal_entries')->insert(['placement_id' => $placement->id, 'week_starts_on' => '2026-11-02', 'content' => 'Synthetic weekly report', 'status' => 'submitted', 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->assertArrayNotHasKey('weekly_report_missing', $this->codes($placement));
    }

    public function test_rule_6_requirement_flag_waits_two_working_days_after_deadline(): void
    {
        $placement = $this->placement();
        $this->certify($placement, 96 * 60);
        $requirement = ProgramTermRequirement::factory()->create(['program_term_id' => $placement->studentEnrollment->program_term_id, 'is_required' => true, 'due_at' => '2026-11-17 10:00:00']);
        $this->assertArrayNotHasKey('requirement_'.$requirement->id, $this->codes($placement)); // 0 working days after
        $requirement->update(['due_at' => '2026-11-15 10:00:00']); // Sunday: Mon–Tue = 2 working days after
        $this->assertArrayHasKey('requirement_'.$requirement->id, $this->codes($placement));
    }

    public function test_scheduled_job_persists_critical_severity_and_defaults_are_active_without_configuration(): void
    {
        $placement = $this->placement();
        $this->assertNull($placement->studentEnrollment->programTerm->monitoring_rules);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseHas('monitoring_flags', ['placement_id' => $placement->id, 'code' => 'auto:cannot_finish', 'severity' => 'critical']);
        $this->assertDatabaseHas('monitoring_flags', ['placement_id' => $placement->id, 'code' => 'auto:behind_schedule', 'severity' => 'warning']);
    }

    public function test_scheduled_job_skips_notifying_an_archived_student_account(): void
    {
        $placement = $this->placement();
        $placement->studentEnrollment->student->user->delete(); // soft-deleted (archived)
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseHas('monitoring_flags', ['placement_id' => $placement->id, 'code' => 'auto:cannot_finish']);
    }

    public function test_rule_7_evaluation_pending_after_window_plus_grace_and_cleared_by_submission(): void
    {
        $placement = $this->placement(240);
        $this->certify($placement, 120 * 60, '2026-11-02'); // half of 240 h reached on Mon 2 Nov: midterm opens
        $this->assertArrayNotHasKey('evaluation_pending', $this->codes($placement)); // 12 working days so far
        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00', 'Asia/Manila')); // 15 working days = 10 open + 5 grace
        $this->assertArrayHasKey('evaluation_pending', $this->codes($placement));
        Evaluation::factory()->create(['placement_id' => $placement->id, 'period' => 'midterm', 'status' => 'submitted', 'submitted_at' => now()]);
        $this->assertArrayNotHasKey('evaluation_pending', $this->codes($placement));
    }

    public function test_configured_holidays_are_not_working_days(): void
    {
        config(['internmatch.holidays' => ['2026-11-16']]);
        $this->assertSame(11, ProgressSummary::workingDays(CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-11-18')));
    }

    public function test_each_rule_notifies_only_the_roles_the_spec_lists(): void
    {
        $placement = $this->placement();
        $supervisor = User::find($placement->supervisor_id);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        // Several flags are raised (behind schedule, cannot finish, no hours, weekly report), but only
        // "cannot finish on time" lists the supervisor, so the supervisor gets exactly one notification.
        $this->assertGreaterThan(1, DB::table('monitoring_flags')->where('placement_id', $placement->id)->count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $supervisor->id)->count());
        $this->assertSame(DB::table('monitoring_flags')->where('placement_id', $placement->id)->count(),
            DB::table('notifications')->where('notifiable_id', $placement->studentEnrollment->student->user_id)->count());
    }

    public function test_saved_program_rule_values_are_validated_and_applied(): void
    {
        $placement = $this->placement();
        $this->certify($placement, 82 * 60); // 82 h ≥ 85% of 96 h: not behind under the default
        $this->assertArrayNotHasKey('behind_schedule', $this->codes($placement));
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($placement->studentEnrollment->programTerm->program_id);
        $url = '/api/v1/program-terms/'.$placement->studentEnrollment->program_term_id.'/monitoring-rules';
        $this->actingAs($coordinator)->putJson($url, ['behind_schedule_percent' => 101, 'approval_reference' => 'Dept memo'])->assertUnprocessable();
        $this->putJson($url, ['remaining_days_threshold' => 14, 'approval_reference' => 'Dept memo'])->assertUnprocessable(); // legacy pair is all-or-nothing
        $this->putJson($url, ['behind_schedule_percent' => 90, 'approval_reference' => 'Dept memo'])->assertOk()
            ->assertJsonPath('data.monitoring_rules.behind_schedule_percent', 90)
            ->assertJsonMissingPath('data.monitoring_rules.remaining_days_threshold');
        $this->assertSame('warning', $this->codes($placement)['behind_schedule']); // 82 h < 90% of 96 h = 86.4 h
        $this->assertDatabaseHas('audit_logs', ['action' => 'monitoring_rules.configured']);
    }
}
