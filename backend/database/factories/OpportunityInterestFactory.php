<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\OpportunityInterest;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpportunityInterest> */
class OpportunityInterestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'opportunity_id' => fn (array $attributes) => Opportunity::factory()->create([
                'academic_term_id' => StudentEnrollment::findOrFail($attributes['student_enrollment_id'])->programTerm->academic_term_id,
            ])->id,
            'expressed_at' => '2026-08-01 09:00:00',
        ];
    }
}
