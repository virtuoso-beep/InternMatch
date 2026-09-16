<?php

namespace App\Policies;

use App\EnrollmentStatus;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Permission;

class StudentEnrollmentPolicy
{
    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($user->hasPermission(Permission::ViewOwnPlacement)) {
            return $enrollment->student->user_id === $user->id;
        }

        return $user->hasPermission(Permission::ViewProgramRecords)
            && $user->isAssignedToProgramTerm($enrollment->program_term_id);
    }

    public function submitRequirements(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->hasPermission(Permission::SubmitOwnRequirements)
            && $enrollment->student->user_id === $user->id
            && $enrollment->status === EnrollmentStatus::Enrolled;
    }
}
