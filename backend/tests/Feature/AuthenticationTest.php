<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:8080']]);
        $this->withHeader('Origin', 'http://localhost:8080')->withCredentials();
    }

    public function test_login_returns_only_explicit_identity_and_permissions_and_rotates_the_session(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->withSession(['marker' => 'retained']);
        $oldId = session()->getId();

        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertOk()->assertExactJson(['data' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => 'student', 'status' => 'active',
            'permissions' => ['manage_own_profile', 'manage_own_competencies', 'submit_own_requirements', 'view_own_placement'],
        ]]);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertSame('retained', session('marker'));

        $this->continueSession($response);
        $this->getJson('/api/user')->assertOk()->assertExactJson($response->json());
    }

    public function test_invalid_credentials_do_not_create_an_authenticated_session(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/login', ['email' => 'invalid', 'password' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_disabled_accounts_cannot_log_in(): void
    {
        $user = User::factory()->withRole(Role::Admin)->create(['status' => AccountStatus::Disabled]);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');
    }

    public function test_pending_accounts_can_identify_themselves_but_have_no_effective_permissions(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk()->assertJsonPath('data.role', null)
            ->assertJsonPath('data.status', 'pending')->assertJsonPath('data.permissions', []);

        $this->continueSession($response);
        $this->getJson('/api/user')->assertOk()->assertJsonPath('data.permissions', []);
        $this->postJson('/api/logout')->assertNoContent();
    }

    public function test_login_cannot_set_role_or_account_status(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/login', [
            'email' => $user->email, 'password' => 'password', 'role' => 'admin', 'status' => 'active',
        ])->assertOk()->assertJsonPath('data.role', null)->assertJsonPath('data.status', 'pending');
        $this->assertNull($user->fresh()->role);
    }

    public function test_guests_cannot_read_identity_or_log_out(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk();
        $this->continueSession($response);
        $oldId = session()->getId();
        $oldToken = session()->token();

        $response = $this->postJson('/api/logout');
        $response->assertNoContent();
        $this->assertGuest('web');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->continueSession($response);
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_disabling_an_already_authenticated_account_blocks_the_next_request(): void
    {
        $user = User::factory()->withRole(Role::Admin)->create();
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk();
        $user->status = AccountStatus::Disabled;
        $user->save();

        $this->continueSession($response);
        $this->getJson('/api/user')->assertForbidden();
        $this->assertGuest('web');
    }

    public function test_five_failed_attempts_are_followed_by_a_temporary_lockout(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest('web');

        $this->travel(61)->seconds();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    }

    public function test_a_successful_login_clears_previous_failures(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        }
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk();
        $this->continueSession($response);
        $response = $this->postJson('/api/logout');
        $response->assertNoContent();
        $this->continueSession($response);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        }
    }

    public function test_login_requires_a_recognized_spa_origin(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->withHeader('Origin', 'https://untrusted.example')->postJson('/api/login', [
            'email' => $user->email, 'password' => 'password',
        ])->assertForbidden();
        $this->assertGuest('web');
    }

    public function test_bearer_tokens_do_not_authenticate_or_require_a_token_table(): void
    {
        $this->withToken('1|untrusted-token')->getJson('/api/user')->assertUnauthorized();
    }

    public function test_real_csrf_middleware_rejects_missing_and_invalid_tokens(): void
    {
        $this->enforceCsrf();
        $user = User::factory()->withRole(Role::Student)->create();
        $credentials = ['email' => $user->email, 'password' => 'password'];

        $this->postJson('/api/login', $credentials)->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'incorrect')->postJson('/api/login', $credentials)->assertStatus(419);
        $this->assertGuest('web');
    }

    public function test_csrf_cookie_bootstrap_supports_login_and_protects_logout(): void
    {
        $this->enforceCsrf();
        $user = User::factory()->withRole(Role::Student)->create();
        $response = $this->getJson('/sanctum/csrf-cookie');
        $response->assertNoContent()->assertCookie('XSRF-TOKEN')->assertCookie(config('session.cookie'));
        $this->continueSession($response);
        $this->withHeader('X-XSRF-TOKEN', $response->getCookie('XSRF-TOKEN', false)->getValue());

        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk();
        $this->continueSession($response);
        $this->withHeader('X-XSRF-TOKEN', '')->postJson('/api/logout')->assertStatus(419);
        $this->withHeader('X-XSRF-TOKEN', $response->getCookie('XSRF-TOKEN', false)->getValue())
            ->postJson('/api/logout')->assertNoContent();
    }

    public function test_cors_allows_credentials_only_for_configured_frontend_origins(): void
    {
        $this->options('/api/login', [], [
            'Origin' => 'http://localhost:8080',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
        ])->assertSuccessful()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

        $this->options('/api/login', [], [
            'Origin' => 'https://untrusted.example', 'Access-Control-Request-Method' => 'POST',
        ])->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    private function continueSession(TestResponse $response): void
    {
        $this->withUnencryptedCookie(config('session.cookie'), $response->getCookie(config('session.cookie'), false)->getValue());
        Auth::forgetGuards();
    }

    private function enforceCsrf(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }
}
