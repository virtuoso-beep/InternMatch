<?php

namespace App\Policies;

use App\Models\Placement;
use App\Models\User;
use App\Permission;

class PlacementPolicy
{
    public function view(User $user, Placement $placement): bool
    {
        return (new StudentEnrollmentPolicy)->view($user, $placement->studentEnrollment)
            || $this->monitor($user, $placement);
    }

    public function decide(User $user, Placement $placement): bool
    {
        return $user->hasPermission(Permission::DecidePlacements)
            && $user->isAssignedToProgramTerm($placement->studentEnrollment->program_term_id);
    }

    public function monitor(User $user, Placement $placement): bool
    {
        return $user->hasPermission(Permission::MonitorAssignedInterns)
            && $placement->supervisor_id === $user->id
            && $user->isAssignedToHost($placement->host_establishment_id);
    }
}
