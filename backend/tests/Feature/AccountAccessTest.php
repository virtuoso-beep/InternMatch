<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost:8080']]);
        $this->withHeader('Origin', 'http://localhost:8080');
    }

    public function test_an_active_admin_can_change_another_accounts_role_and_status_only(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'web')->patchJson('/api/users/'.$user->id.'/access', [
            'role' => 'coordinator', 'status' => 'active', 'name' => 'Overwritten', 'password' => 'changed',
        ])->assertOk()->assertJsonPath('data.role', 'coordinator')->assertJsonPath('data.status', 'active');

        $fresh = $user->fresh();
        $this->assertSame(Role::Coordinator, $fresh->role);
        $this->assertSame(AccountStatus::Active, $fresh->status);
        $this->assertSame($user->name, $fresh->name);
        $this->assertSame($user->password, $fresh->password);
    }

    public function test_an_admin_can_change_only_status_without_changing_role(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $user = User::factory()->withRole(Role::Student)->create();
        $this->actingAs($admin, 'web')->patchJson('/api/users/'.$user->id.'/access', [
            'status' => 'disabled',
        ])->assertOk()->assertJsonPath('data.permissions', []);
        $this->assertSame(Role::Student, $user->fresh()->role);
    }

    public function test_an_admin_cannot_modify_their_own_access(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin, 'web')->patchJson('/api/users/'.$admin->id.'/access', [
            'role' => 'coordinator', 'status' => 'disabled',
        ])->assertForbidden();
        $this->assertSame(Role::Admin, $admin->fresh()->role);
        $this->assertSame(AccountStatus::Active, $admin->fresh()->status);
    }

    public static function nonAdminRoles(): array
    {
        return [
            'student' => [Role::Student], 'coordinator' => [Role::Coordinator],
            'supervisor' => [Role::Supervisor], 'dean' => [Role::Dean],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_other_roles_cannot_elevate_themselves_or_another_account(Role $role): void
    {
        $actor = User::factory()->withRole($role)->create();
        $other = User::factory()->create();
        $this->actingAs($actor, 'web');
        foreach ([$actor, $other] as $target) {
            $this->patchJson('/api/users/'.$target->id.'/access', [
                'role' => 'admin', 'status' => 'active',
            ])->assertForbidden();
        }
        $this->assertSame($role, $actor->fresh()->role);
        $this->assertNull($other->fresh()->role);
    }

    public static function inactiveStatuses(): array
    {
        return ['pending' => [AccountStatus::Pending], 'disabled' => [AccountStatus::Disabled]];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_inactive_admins_cannot_manage_accounts(AccountStatus $status): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create(['status' => $status]);
        $user = User::factory()->create();
        $this->actingAs($admin, 'web')->patchJson('/api/users/'.$user->id.'/access', [
            'role' => 'admin', 'status' => 'active',
        ])->assertForbidden();
        $this->assertNull($user->fresh()->role);
    }

    public function test_guest_cannot_change_account_access(): void
    {
        $user = User::factory()->create();
        $this->patchJson('/api/users/'.$user->id.'/access', ['role' => 'admin'])->assertUnauthorized();
    }

    public function test_access_changes_require_valid_fixed_enum_values(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $user = User::factory()->create();
        $this->actingAs($admin, 'web')->patchJson('/api/users/'.$user->id.'/access', [
            'role' => 'super_admin', 'status' => 'approved',
        ])->assertUnprocessable()->assertJsonValidationErrors(['role', 'status']);
        $this->patchJson('/api/users/'.$user->id.'/access', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['role', 'status']);
        $this->assertNull($user->fresh()->role);
    }

    public function test_permission_gates_match_the_existing_role_and_status_matrix(): void
    {
        foreach (Role::cases() as $role) {
            foreach (AccountStatus::cases() as $status) {
                $user = User::factory()->withRole($role)->make(['status' => $status]);
                foreach (Permission::cases() as $permission) {
                    $this->assertSame(
                        $status === AccountStatus::Active && $role->allows($permission),
                        Gate::forUser($user)->allows($permission->value),
                    );
                }
            }
        }
    }

    public function test_http_authorization_preserves_coordinator_assignment_and_rejects_admin_bypass(): void
    {
        // A test-only endpoint exercises the real middleware and policy without adding placement operations.
        Route::post('/api/test/placements/{placement}/decide', fn (Placement $placement) => response()->noContent())
            ->middleware(['api', 'auth:sanctum', 'account.enabled', 'can:decide_placements', 'can:decide,placement']);

        $placement = Placement::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $url = '/api/test/placements/'.$placement->id.'/decide';

        $this->actingAs($coordinator, 'web')->postJson($url)->assertForbidden();
        $coordinator->programTerms()->attach($placement->studentEnrollment->program_term_id);
        $this->postJson($url)->assertNoContent();

        $this->app['auth']->forgetGuards();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $admin->programTerms()->attach($placement->studentEnrollment->program_term_id);
        $this->actingAs($admin, 'web')->postJson($url)->assertForbidden();
    }
}
