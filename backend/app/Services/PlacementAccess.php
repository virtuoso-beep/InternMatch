<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PlacementAccess
{
    public static function query(User $user): Builder
    {
        $query = Placement::query();

        return match ($user->role) {
            Role::Admin => $query,
            Role::Student => $query->whereHas('studentEnrollment.student', fn ($query) => $query->where('user_id', $user->id)),
            Role::Coordinator => $query->whereHas('studentEnrollment.programTerm', fn ($query) => $query->whereIn('program_id', $user->programs()->select('programs.id'))),
            Role::Supervisor => $query->where('supervisor_id', $user->id)->whereIn('host_establishment_id', $user->hostEstablishments()->select('host_establishments.id')),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
