<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data must never create accounts or reset existing credentials.
        $this->call([ProgramSeeder::class, InternMatchReferenceSeeder::class]);
    }
}
