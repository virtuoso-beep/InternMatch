<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityManagement;

class OpportunityPolicy
{
    public function update(User $user, Opportunity $opportunity): bool
    {
        if (! $user->hasPermission(Permission::ManageHosts) || ! OpportunityManagement::query($user)->whereKey($opportunity->id)->exists()) {
            return false;
        }

        return $user->role !== Role::Coordinator
            || array_diff($opportunity->programs()->pluck('programs.id')->all(), $user->programs()->pluck('programs.id')->all()) === [];
    }
}
