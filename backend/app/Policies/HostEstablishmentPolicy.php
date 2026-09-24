<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\HostEstablishment;
use App\Models\User;
use App\Services\HostAccess;

class HostEstablishmentPolicy
{
    public function update(User $user, HostEstablishment $host): bool
    {
        return $user->hasPermission(Permission::ManageHosts) && HostAccess::query($user)->whereKey($host->id)->exists();
    }
}
