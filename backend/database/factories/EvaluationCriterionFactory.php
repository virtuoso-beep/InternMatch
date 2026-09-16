<?php

namespace Database\Factories;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvaluationCriterion> */
class EvaluationCriterionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'evaluation_rubric_id' => EvaluationRubric::factory(),
            'name' => fake()->unique()->words(3, true),
            'max_score' => 5,
            'weight' => 1,
        ];
    }
}
