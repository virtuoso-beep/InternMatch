<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\Evaluation;
use App\Models\EvaluationRubric;
use App\Models\Placement;
use App\Models\User;
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
