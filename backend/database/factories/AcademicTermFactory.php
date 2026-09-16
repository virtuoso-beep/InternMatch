<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicTerm> */
class AcademicTermFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TERM-####??'),
            'academic_year' => '2026-2027',
            'name' => 'First semester',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-12-31',
            'is_active' => true,
        ];
    }
}
