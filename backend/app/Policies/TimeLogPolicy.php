<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    public function view(User $user, TimeLog $timeLog): bool
    {
        return (new PlacementPolicy)->view($user, $timeLog->placement);
    }

    public function verify(User $user, TimeLog $timeLog): bool
    {
        return (new PlacementPolicy)->monitor($user, $timeLog->placement);
    }
}
