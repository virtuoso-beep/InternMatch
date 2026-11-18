<?php

namespace Tests\Feature;

use App\Enums\MoaStatus;
use App\Enums\Role;
use App\Models\Moa;
use App\Models\Program;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Spec §5: MOA status is computed from the dates; a 90-day expiry notice goes to coordinators and administrators. */
class MoaLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_status_is_derived_from_dates_at_the_ninety_day_boundary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00'));
        $moa = fn (array $attrs) => Moa::factory()->make($attrs);
        $this->assertSame('active', $moa(['expires_on' => '2027-02-17'])->lifecycle_status); // 91 days left
        $this->assertSame('expiring', $moa(['expires_on' => '2027-02-16'])->lifecycle_status); // 90 days left
        $this->assertSame('expiring', $moa(['expires_on' => '2026-11-18'])->lifecycle_status); // last day
        $this->assertSame('expired', $moa(['expires_on' => '2026-11-17'])->lifecycle_status);
        $this->assertSame('pending', $moa(['effective_on' => '2026-11-19'])->lifecycle_status);
        $this->assertSame('pending', $moa(['status' => MoaStatus::PendingSignature, 'effective_on' => null])->lifecycle_status);
        $this->assertSame('terminated', $moa(['status' => MoaStatus::Terminated])->lifecycle_status);
    }

    public function test_expiring_notice_reaches_covered_coordinators_and_admins_once(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00'));
        [$covered, $other] = Program::factory()->count(2)->create();
        $moa = Moa::factory()->create(['expires_on' => '2027-01-31']);
        $moa->programs()->attach($covered->id);
        Moa::factory()->create(['expires_on' => '2027-12-31'])->programs()->attach($covered->id); // not yet expiring
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($covered->id);
        $outsider = User::factory()->withRole(Role::Coordinator)->create();
        $outsider->programs()->attach($other->id);
        $admin = User::factory()->withRole(Role::Admin)->create();
        $dean = User::factory()->withRole(Role::Dean)->create();

        $this->artisan('internmatch:notify-expiring-moas')->assertSuccessful();
        $this->artisan('internmatch:notify-expiring-moas')->assertSuccessful(); // re-run sends nothing new

        $count = fn (User $u) => DB::table('notifications')->where('notifiable_id', $u->id)->count();
        $this->assertSame(1, $count($coordinator));
        $this->assertSame(1, $count($admin));
        $this->assertSame(0, $count($outsider));
        $this->assertSame(0, $count($dean));
        $this->assertStringContainsString($moa->reference_number, json_decode(DB::table('notifications')->where('notifiable_id', $admin->id)->value('data'), true)['body']);
    }
}
