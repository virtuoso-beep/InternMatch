<?php

namespace Database\Factories;

use App\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Competency> */
class CompetencyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SKILL-######'),
            'name' => fake()->words(3, true),
        ];
    }
}
