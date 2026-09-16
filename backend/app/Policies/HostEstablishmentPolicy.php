<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\HostEstablishment;
use App\Models\User;

class HostEstablishmentPolicy
{
    public function view(User $user, HostEstablishment $host): bool
    {
        return $user->hasPermission(Permission::ManageHosts)
            || $user->hasPermission(Permission::ViewOwnPlacement)
            || $user->hasPermission(Permission::ViewReports)
            || ($user->hasPermission(Permission::ManageAssignedHost) && $user->isAssignedToHost($host->id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageHosts);
    }

    public function update(User $user, HostEstablishment $host): bool
    {
        return $user->hasPermission(Permission::ManageHosts)
            || ($user->hasPermission(Permission::ManageAssignedHost) && $user->isAssignedToHost($host->id));
    }
}
