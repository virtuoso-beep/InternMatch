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
        // Demo accounts are a development convenience only. Never create them in
        // production, and never reset credentials of accounts that already exist
        // (the container entrypoint runs this seeder on every start).
        if (app()->isProduction()) {
            return;
        }

        foreach ([
            ['name' => 'Student Demo', 'email' => 'student@example.com', 'role' => Role::Student],
            ['name' => 'Supervisor Demo', 'email' => 'supervisor@example.com', 'role' => Role::Supervisor],
            ['name' => 'Dean Demo', 'email' => 'dean@example.com', 'role' => Role::Dean],
            ['name' => 'Admin Demo', 'email' => 'admin@example.com', 'role' => Role::Admin],
        ] as $account) {
            if (User::withTrashed()->where('email', $account['email'])->exists()) {
                continue;
            }

            User::create(
                [
                    'email' => $account['email'],
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
