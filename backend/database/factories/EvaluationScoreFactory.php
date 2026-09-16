<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvaluationScore> */
class EvaluationScoreFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'evaluation_rubric_id' => fn (array $attributes) => Evaluation::findOrFail($attributes['evaluation_id'])->evaluation_rubric_id,
            'evaluation_criterion_id' => fn (array $attributes) => EvaluationCriterion::factory()->create(['evaluation_rubric_id' => $attributes['evaluation_rubric_id']])->id,
            'score' => 4,
        ];
    }
}
