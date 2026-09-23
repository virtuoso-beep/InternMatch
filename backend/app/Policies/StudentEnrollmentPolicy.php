<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->hasPermission(Permission::ManageAcademicRecords)
            || ($user->role === Role::Coordinator
                && $user->hasPermission(Permission::ViewProgramRecords)
                && $user->isAssignedToProgramTerm($enrollment->program_term_id));
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($user->hasPermission(Permission::ManageSystem)) {
            return true;
        }
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
