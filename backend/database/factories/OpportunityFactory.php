<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\OpportunityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Opportunity> */
class OpportunityFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_establishment_id' => HostEstablishment::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'title' => 'Web Development Intern',
            'description' => fake()->paragraph(),
            'capacity' => 4,
            'status' => OpportunityStatus::Published,
        ];
    }
}
