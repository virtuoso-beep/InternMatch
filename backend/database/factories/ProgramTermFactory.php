<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\ProgramTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProgramTerm> */
class ProgramTermFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'required_minutes' => 24000,
        ];
    }
}
