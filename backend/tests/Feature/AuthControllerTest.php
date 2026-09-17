<?php

namespace Tests\Feature;

use App\AccountStatus;
use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function roles(): array
    {
        return array_map(fn (Role $role): array => [$role], Role::cases());
    }

    #[DataProvider('roles')]
    public function test_login_uses_the_database_role_even_when_another_role_is_submitted(Role $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password', 'role' => 'admin'])
            ->assertOk()->assertJsonPath('data.role', $role->value)->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_returns_422_and_does_not_authenticate(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public static function inactive(): array
    {
        return [[AccountStatus::Pending], [AccountStatus::Disabled]];
    }

    #[DataProvider('inactive')]
    public function test_inactive_accounts_cannot_login(AccountStatus $status): void
    {
        $user = User::factory()->withRole(Role::Student)->create(['status' => $status]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_account_without_assigned_role_cannot_login(): void
    {
        $user = User::factory()->create(['status' => AccountStatus::Active]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_missing_credentials_return_field_errors(): void
    {
        $this->postJson('/api/v1/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_guest_cannot_read_session(): void
    {
        $this->getJson('/api/v1/session')->assertUnauthorized();
    }

    public function test_session_only_returns_the_authenticated_account(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();

        $this->actingAs($user)->getJson('/api/v1/session')->assertOk()
            ->assertJsonPath('data.email', $user->email)->assertJsonMissingPath('data.password');
    }

    public function test_disabled_existing_session_is_denied_and_logged_out(): void
    {
        $user = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Disabled]);

        $this->actingAs($user)->getJson('/api/v1/session')->assertForbidden();

        $this->assertGuest();
    }

    public function test_logout_ends_authentication(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();

        $this->actingAs($user)->postJson('/api/v1/logout')->assertNoContent();

        $this->assertGuest();
        $this->getJson('/api/v1/session')->assertUnauthorized();
    }

    public function test_sixth_login_attempt_is_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])->assertTooManyRequests();
    }

    public function test_student_cannot_use_a_coordinator_endpoint(): void
    {
        Route::middleware(['web', 'auth', 'active', 'role:coordinator'])->get('/api/v1/test-coordinator', fn () => ['ok' => true]);
        $user = User::factory()->withRole(Role::Student)->create();

        $this->actingAs($user)->getJson('/api/v1/test-coordinator')->assertForbidden();
    }

    public function test_coordinator_can_use_a_coordinator_endpoint(): void
    {
        Route::middleware(['web', 'auth', 'active', 'role:coordinator'])->get('/api/v1/test-coordinator', fn () => ['ok' => true]);
        $user = User::factory()->withRole(Role::Coordinator)->create();

        $this->actingAs($user)->getJson('/api/v1/test-coordinator')->assertOk()->assertJsonPath('ok', true);
    }
}
