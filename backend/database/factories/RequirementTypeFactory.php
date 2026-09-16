<?php

namespace Database\Factories;

use App\Models\RequirementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RequirementType> */
class RequirementTypeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('REQ-######'),
            'name' => fake()->words(3, true),
        ];
    }
}
