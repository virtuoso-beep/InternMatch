<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\ProgramTerm;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentEnrollment> */
class StudentEnrollmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'program_term_id' => ProgramTerm::factory(),
            'required_minutes' => fn (array $attributes) => ProgramTerm::findOrFail($attributes['program_term_id'])->required_minutes,
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_on' => '2026-08-01',
        ];
    }
}
