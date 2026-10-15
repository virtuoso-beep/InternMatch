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
        $this->assertDatabaseMissing('users', ['email' => 'coordinator@example.com']);
        $this->assertSame(5, User::withTrashed()->count());
    }
}
