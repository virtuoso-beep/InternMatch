<?php

namespace Database\Factories;

use App\Models\EvaluationRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvaluationRubric> */
class EvaluationRubricFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('RUBRIC-######'),
            'version' => 1,
            'name' => 'Supervisor evaluation',
            'is_active' => true,
        ];
    }
}
