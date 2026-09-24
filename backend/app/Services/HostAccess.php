<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HostAccess
{
    public static function query(User $user): Builder
    {
        $query = HostEstablishment::query();
        if ($user->status !== AccountStatus::Active) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->role === Role::Admin) {
            return $query;
        }
        if ($user->role === Role::Supervisor) {
            return $query->whereIn('id', $user->hostEstablishments()->select('host_establishments.id'));
        }
        if ($user->role === Role::Coordinator) {
            $programs = $user->programs()->pluck('programs.id');

            return $query->where(function ($hosts) use ($programs) {
                $hosts->whereIn('id', DB::table('host_program_capacity')->whereIn('program_id', $programs)->select('host_establishment_id'))
                    ->orWhereHas('opportunities.programs', fn ($query) => $query->whereIn('programs.id', $programs))
                    ->orWhereHas('moas.programs', fn ($query) => $query->whereIn('programs.id', $programs));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function authorizePrograms(User $user, array $programIds): void
    {
        if ($user->role === Role::Coordinator) {
            abort_if(array_diff($programIds, $user->programs()->pluck('programs.id')->all()) !== [], 403, 'A program is outside your assigned scope.');
        }
    }
}
