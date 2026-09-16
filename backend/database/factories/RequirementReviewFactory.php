<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\RequirementReview;
use App\Models\RequirementSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RequirementReview> */
class RequirementReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'requirement_submission_id' => RequirementSubmission::factory(),
            'reviewed_by' => User::factory()->state(['role' => Role::Coordinator, 'status' => AccountStatus::Active]),
            'decision' => SubmissionStatus::Approved,
            'reviewed_at' => '2026-08-03 09:00:00',
        ];
    }
}
