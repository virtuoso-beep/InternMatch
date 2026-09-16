<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RequirementSubmission> */
class RequirementSubmissionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'program_term_id' => fn (array $attributes) => StudentEnrollment::findOrFail($attributes['student_enrollment_id'])->program_term_id,
            'program_term_requirement_id' => fn (array $attributes) => ProgramTermRequirement::factory()->create(['program_term_id' => $attributes['program_term_id']])->id,
            'document_id' => Document::factory(),
            'revision' => 1,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => '2026-08-02 09:00:00',
        ];
    }
}
