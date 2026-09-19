<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\SubmissionStatus;
use App\Models\RequirementSubmission;
use App\Models\User;

class RequirementSubmissionPolicy
{
    public function view(User $user, RequirementSubmission $submission): bool
    {
        return $user->hasPermission(Permission::ManageSystem)
            || ($user->hasPermission(Permission::SubmitOwnRequirements)
            && $submission->studentEnrollment->student->user_id === $user->id)
            || ($user->hasPermission(Permission::ReviewRequirements)
                && $user->isAssignedToProgramTerm($submission->program_term_id));
    }

    public function review(User $user, RequirementSubmission $submission): bool
    {
        return $user->hasPermission(Permission::ReviewRequirements)
            && $user->isAssignedToProgramTerm($submission->program_term_id)
            && in_array($submission->status, [SubmissionStatus::Submitted, SubmissionStatus::UnderReview], true);
    }
}
