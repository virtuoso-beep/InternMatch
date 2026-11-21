<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Every role can change its password after the first sign-in. */
class PasswordChangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CURRENT = 'Current-Pass-2026!';

    private const NEXT = 'Replacement-Pass-2026!';

    public function test_every_role_can_change_its_password_with_the_current_one(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->withRole($role)->create(['password' => self::CURRENT, 'must_change_password' => false]);
            $this->actingAs($user)->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => self::NEXT])->assertOk();
            $this->assertTrue(Hash::check(self::NEXT, $user->fresh()->password), $role->value);
            $this->assertDatabaseHas('audit_logs', ['action' => 'account.password_changed', 'subject_id' => $user->id]);
        }
    }

    public function test_wrong_current_weak_or_unconfirmed_passwords_are_rejected_and_nothing_changes(): void
    {
        $user = User::factory()->withRole(Role::Student)->create(['password' => self::CURRENT, 'must_change_password' => false]);
        $this->actingAs($user);
        $this->putJson('/api/v1/account/password', ['current_password' => 'Wrong-Pass-2026!', 'password' => self::NEXT, 'password_confirmation' => self::NEXT])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => 'Different-Pass-2026!'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => self::CURRENT, 'password_confirmation' => self::CURRENT])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }

    public function test_first_password_setup_and_guests_use_their_own_paths(): void
    {
        $this->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => self::NEXT])->assertUnauthorized();
        $user = User::factory()->withRole(Role::Student)->create(['password' => self::CURRENT, 'must_change_password' => true]);
        $this->actingAs($user)->putJson('/api/v1/account/password', ['current_password' => self::CURRENT, 'password' => self::NEXT, 'password_confirmation' => self::NEXT])->assertForbidden();
        $this->assertTrue(Hash::check(self::CURRENT, $user->fresh()->password));
    }
}
