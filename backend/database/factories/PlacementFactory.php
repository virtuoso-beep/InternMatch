<?php

namespace Database\Factories;

use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\PlacementStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Placement> */
class PlacementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'opportunity_id' => fn (array $attributes) => Opportunity::factory()->create(['academic_term_id' => StudentEnrollment::findOrFail($attributes['student_enrollment_id'])->programTerm->academic_term_id])->id,
            'host_establishment_id' => fn (array $attributes) => Opportunity::findOrFail($attributes['opportunity_id'])->host_establishment_id,
            'moa_id' => fn (array $attributes) => Moa::factory()->create(['host_establishment_id' => $attributes['host_establishment_id']])->id,
            'status' => PlacementStatus::Pending,
        ];
    }
}
