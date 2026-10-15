<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Student Demo', 'email' => 'student@example.com', 'role' => Role::Student],
            ['name' => 'Richard Misa', 'email' => 'r.misa@example.com', 'role' => Role::Coordinator],
            ['name' => 'Supervisor Demo', 'email' => 'supervisor@example.com', 'role' => Role::Supervisor],
            ['name' => 'Dean Demo', 'email' => 'dean@example.com', 'role' => Role::Dean],
            ['name' => 'Admin Demo', 'email' => 'admin@example.com', 'role' => Role::Admin],
        ] as $account) {
            User::withTrashed()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role' => $account['role'],
                    'status' => AccountStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
