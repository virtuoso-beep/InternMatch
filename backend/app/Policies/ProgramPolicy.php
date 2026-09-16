<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use App\Permission;

class ProgramPolicy
{
    public function view(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ManageAcademicRecords)
            || ($user->hasPermission(Permission::ViewProgramRecords)
                && $user->programTerms()->where('program_id', $program->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAcademicRecords);
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ManageAcademicRecords);
    }
}
