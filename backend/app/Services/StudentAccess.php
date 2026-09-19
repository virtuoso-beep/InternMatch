<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentAccess
{
    public static function enrollments(User $user): Builder
    {
        $query = StudentEnrollment::query();
        if ($user->status !== AccountStatus::Active) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->role) {
            Role::Admin => $query,
            Role::Student => $query->whereHas('student', fn ($student) => $student->where('user_id', $user->id)),
            Role::Coordinator, Role::Dean => $query->whereHas('programTerm', fn ($term) => $term->whereIn('program_id', $user->programs()->select('programs.id'))),
            Role::Supervisor => $query->whereHas('currentPlacement', fn ($placement) => $placement->where('supervisor_id', $user->id)
                ->whereIn('host_establishment_id', $user->hostEstablishments()->select('host_establishments.id'))),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
