<?php

namespace Database\Factories;

use App\AccountStatus;
use App\Models\Student;
use App\Models\User;
use App\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Student, 'status' => AccountStatus::Active]),
            'student_number' => fake()->unique()->bothify('STU-########'),
        ];
    }
}
