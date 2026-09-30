<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{Placement, User};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduledMonitoringTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_scheduled_flags_notify_once_resolve_and_can_recur_without_notifying_unassigned_users(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => today()->subMonth(), 'ends_on' => today()->addDay()]);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $term = $placement->studentEnrollment->programTerm;
        $term->program->update(['required_ojt_hours' => 486]);
        $term->update(['monitoring_rules' => ['remaining_days_threshold' => 7, 'remaining_hours_threshold' => 100, 'approval_reference' => 'Synthetic test only']]);
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

    public function test_unconfigured_hours_thresholds_do_not_create_predicted_risk(): void
    {
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => User::factory()->withRole(Role::Supervisor), 'starts_on' => today()->subMonth(), 'ends_on' => today()]);
        $placement->studentEnrollment->programTerm->program->update(['required_ojt_hours' => 486]);
        $this->artisan('internmatch:refresh-monitoring')->assertSuccessful();
        $this->assertDatabaseCount('monitoring_flags', 0);
        $this->assertDatabaseCount('notifications', 0);
    }
}
