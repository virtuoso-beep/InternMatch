<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeding_preserves_existing_credentials_and_retired_accounts(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create([
            'email' => 'r.misa@example.com', 'name' => 'Richard Misa',
        ]);
        $password = $coordinator->password;
        $student = User::factory()->create([
            'email' => 'student@example.com', 'status' => AccountStatus::Disabled,
        ]);
        $student->delete();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($password, $coordinator->fresh()->password);
        $this->assertSame('Richard Misa', $coordinator->fresh()->name);
        $this->assertTrue(User::withTrashed()->findOrFail($student->id)->trashed());
        $this->assertSame(AccountStatus::Disabled, User::withTrashed()->findOrFail($student->id)->status);
        // No demo coordinator is seeded (removed 2026-11-17); other missing demo accounts are created once.
        $this->assertDatabaseMissing('users', ['email' => 'coordinator@example.com']);
        $this->assertSame(5, User::withTrashed()->count());
    }

    public function test_seeding_never_resets_an_existing_demo_account(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create([
            'email' => 'admin@example.com', 'status' => AccountStatus::Disabled,
        ]);
        $hash = $admin->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertSame(AccountStatus::Disabled, $admin->fresh()->status);
    }

    public function test_seeding_creates_no_demo_accounts_in_production(): void
    {
        User::count(); // trigger the lazy database refresh before leaving the testing environment
        $this->app->detectEnvironment(fn () => 'production');

        // Invoke directly: the db:seed command is itself guarded in production.
        (new DatabaseSeeder)->run();

        $this->assertSame(0, User::withTrashed()->count());
    }
}
