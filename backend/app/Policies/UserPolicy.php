<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function updateAccess(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::ManageAccounts)
            && $actor->id !== $user->id;
    }
}
