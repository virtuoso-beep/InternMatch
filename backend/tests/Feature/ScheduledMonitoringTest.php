<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduledMonitoringTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_scheduled_flags_notify_once_resolve_and_can_recur_without_notifying_unassigned_users(): void
    {
        // Fixed Wednesday so working-day arithmetic is deterministic; spec §13 pace rules are turned
        // down for this fixture so the test isolates the scheduler mechanics on the legacy rule.
        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00', 'Asia/Manila'));
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => today()->subMonth(), 'ends_on' => today()->addDay()]);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $term = $placement->studentEnrollment->programTerm;
        $term->program->update(['required_ojt_hours' => 486]);
        $term->update(['monitoring_rules' => ['remaining_days_threshold' => 7, 'remaining_hours_threshold' => 100, 'approval_reference' => 'Synthetic test only', 'behind_schedule_after_days' => 100000, 'max_daily_hours' => 100000, 'no_log_days' => 100000, 'uncertified_days' => 100000, 'weekly_report_grace_days' => 100000]]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($term->program_id);
        $other = User::factory()->withRole(Role::Coordinator)->create();
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseCount('monitoring_flags', 1);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertFalse(DB::table('notifications')->where('notifiable_id', $other->id)->exists());
        $placement->update(['ends_on' => today()->addMonth()]);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertNotNull(DB::table('monitoring_flags')->first()->resolved_at);
        $this->assertDatabaseCount('notifications', 3);
        $placement->update(['ends_on' => today()->addDay()]);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseCount('monitoring_flags', 2);
        $this->assertDatabaseCount('notifications', 6);
        $this->assertDatabaseHas('audit_logs', ['action' => 'monitoring.resolved', 'actor_id' => null]);
    }

    public function test_spec_default_rules_are_active_without_department_thresholds(): void
    {
        // Spec §13 (17 Nov 2026): the adopted defaults are active for every program; no thresholds need configuring.
        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00', 'Asia/Manila'));
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => User::factory()->withRole(Role::Supervisor), 'starts_on' => '2026-10-19', 'ends_on' => '2026-11-18']);
        $placement->studentEnrollment->programTerm->program->update(['required_ojt_hours' => 486]);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseHas('monitoring_flags', ['placement_id' => $placement->id, 'code' => 'auto:behind_schedule', 'severity' => 'warning']);
        $this->assertDatabaseHas('monitoring_flags', ['placement_id' => $placement->id, 'code' => 'auto:cannot_finish', 'severity' => 'critical']);
        $this->assertDatabaseMissing('monitoring_flags', ['code' => 'auto:insufficient_hours']); // legacy rule stays off without thresholds
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $placement->studentEnrollment->student->user_id)->exists());
    }
}
