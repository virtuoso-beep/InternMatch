<?php

namespace Database\Factories;

use App\Models\ProgramTerm;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProgramTermRequirement> */
class ProgramTermRequirementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'program_term_id' => ProgramTerm::factory(),
            'requirement_type_id' => RequirementType::factory(),
            'is_required' => true,
            'required_before_deployment' => true,
        ];
    }
}
