<?php

namespace Database\Factories;

use App\AccountStatus;
use App\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\EvaluationRubric;
use App\Models\Placement;
use App\Models\User;
use App\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Evaluation> */
class EvaluationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'evaluation_rubric_id' => EvaluationRubric::factory(),
            'evaluator_id' => User::factory()->state(['role' => Role::Supervisor, 'status' => AccountStatus::Active]),
            'period' => 'midterm',
            'status' => EvaluationStatus::Draft,
        ];
    }
}
