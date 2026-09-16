<?php

namespace Database\Factories;

use App\Models\Competency;
use App\Models\Student;
use App\Models\StudentCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentCompetency> */
class StudentCompetencyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'competency_id' => Competency::factory(),
            'level' => 75,
            'assessed_at' => '2026-08-01 09:00:00',
        ];
    }
}
