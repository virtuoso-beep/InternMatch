<?php

namespace Database\Factories;

use App\Models\CompetencyEvidence;
use App\Models\StudentCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompetencyEvidence> */
class CompetencyEvidenceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_competency_id' => StudentCompetency::factory(),
            'source' => 'self_assessment',
            'description' => fake()->sentence(),
        ];
    }
}
