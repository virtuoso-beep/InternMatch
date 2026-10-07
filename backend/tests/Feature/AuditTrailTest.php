<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_audit_reads_are_scoped_and_do_not_expose_change_payloads(): void
    {
        $own = Program::factory()->create();
        $outside = Program::factory()->create();
        $admin = User::factory()->withRole(Role::Admin)->create();
        Audit::record($admin, 'test.own', $own, ['private_detail' => 'do-not-expose'], $own->id);
        Audit::record($admin, 'test.foreign', $outside, [], $outside->id);
        Audit::record($admin, 'test.system', $admin, []);
        foreach ([Role::Coordinator, Role::Dean] as $role) {
            $actor = User::factory()->withRole($role)->create();
            $actor->programs()->attach($own->id);
            $response = $this->actingAs($actor)->getJson('/api/v1/audit-logs')->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.action', 'test.own');
            $this->assertStringNotContainsString('do-not-expose', $response->getContent());
        }
        $this->actingAs($admin)->getJson('/api/v1/audit-logs')->assertOk()->assertJsonCount(3, 'data');
        foreach ([Role::Student, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/audit-logs')->assertForbidden();
        }
    }
}
