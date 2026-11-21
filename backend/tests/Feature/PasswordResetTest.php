<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\PasswordResetLink;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const NEW_PASSWORD = 'Reset-Passw0rd-2026!';

    public function test_every_address_gets_the_same_answer_and_only_active_accounts_get_mail(): void
    {
        Notification::fake();
        $active = User::factory()->withRole(Role::Student)->create();
        $pending = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Pending]);
        $disabled = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Disabled]);

        $answers = collect(['nobody@example.test', $pending->email, $disabled->email, strtoupper($active->email)])
            ->map(fn (string $email) => $this->postJson('/api/v1/forgot-password', ['email' => $email])->assertStatus(202)->json('message'));

        $this->assertCount(1, $answers->unique(), 'The answer must not reveal whether an account exists.');
        Notification::assertSentTo($active, PasswordResetLink::class, function (PasswordResetLink $notification) use ($active) {
            $url = $notification->toMail($active)->actionUrl;

            return str_contains($url, '/reset-password?token=') && str_contains($url, 'email='.urlencode($active->email));
        });
        Notification::assertNotSentTo([$pending, $disabled], PasswordResetLink::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.password_reset_requested']);
    }

    public function test_reset_sets_the_password_once_ends_other_sessions_and_is_audited(): void
    {
        $user = User::factory()->withRole(Role::Coordinator)->create();
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $token = Password::broker()->createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD];

        $this->postJson('/api/v1/reset-password', $payload)->assertOk()->assertJsonPath('data.reset', true);

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.password_reset']);
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => self::NEW_PASSWORD])->assertOk();

        $this->postJson('/api/v1/logout');
        $this->postJson('/api/v1/reset-password', $payload)->assertUnprocessable()->assertJsonValidationErrors('token');
    }

    public function test_weak_passwords_wrong_tokens_expired_links_and_inactive_accounts_are_rejected(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $token = Password::broker()->createToken($user);
        $base = ['email' => $user->email, 'token' => $token];

        $this->postJson('/api/v1/reset-password', $base + ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/reset-password', ['token' => 'wrong'] + $base + ['password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD])
            ->assertUnprocessable()->assertJsonValidationErrors('token');

        $this->travel(61)->minutes();
        $this->postJson('/api/v1/reset-password', $base + ['password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->travelBack();

        $disabled = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Disabled]);
        $this->postJson('/api/v1/reset-password', ['email' => $disabled->email, 'token' => Password::broker()->createToken($disabled), 'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertFalse(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
    }

    public function test_requests_are_throttled_per_address(): void
    {
        Notification::fake();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/forgot-password', ['email' => 'flood@example.test'])->assertStatus(202);
        }
        $this->postJson('/api/v1/forgot-password', ['email' => 'flood@example.test'])
            ->assertTooManyRequests()->assertHeader('Retry-After')->assertJsonStructure(['message', 'retry_after']);
    }
}
