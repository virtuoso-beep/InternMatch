<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class CreateFirstAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PASSWORD = 'Strong-Pass-2026!';

    private function prompts(string $password = self::PASSWORD, string $again = self::PASSWORD): PendingCommand
    {
        return $this->artisan('internmatch:create-first-admin', ['email' => 'registrar@umindanao.edu.ph', '--name' => 'System Administrator'])
            ->expectsQuestion('Password (at least 12 characters, upper and lower case, a number and a symbol)', $password)
            ->expectsQuestion('Type the password again', $again);
    }

    public function test_creates_an_active_admin_with_a_hashed_password_and_an_audit_entry(): void
    {
        $this->prompts()->expectsOutputToContain('Administrator account')->assertSuccessful();

        $admin = User::where('email', 'registrar@umindanao.edu.ph')->firstOrFail();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertSame(AccountStatus::Active, $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertNotSame(self::PASSWORD, $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.first_admin_created', 'actor_id' => $admin->id]);
    }

    public function test_refuses_weak_or_mismatched_passwords_and_creates_nothing(): void
    {
        $this->prompts('short', 'short')->assertFailed();
        $this->prompts(self::PASSWORD, 'Different-Pass-2026!')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_refuses_when_any_administrator_already_exists(): void
    {
        $existing = User::factory()->withRole(Role::Admin)->create();
        $existing->delete(); // even an archived administrator counts

        $this->artisan('internmatch:create-first-admin', ['email' => 'registrar@umindanao.edu.ph'])
            ->expectsOutputToContain('already exists')->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'registrar@umindanao.edu.ph']);
    }
}
