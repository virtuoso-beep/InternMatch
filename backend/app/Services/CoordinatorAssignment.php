<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Program assignments for coordinators and program chairs/deans (spec §2).
 * - A coordinator holds at most ONE active program; a program may have many coordinators.
 * - Deans may hold several programs (scoped to a program or department).
 * - Ending an assignment sets removed_at; rows are never deleted, so history and audit attribution remain.
 * Callers run this inside their transaction and record their own audit entry.
 */
class CoordinatorAssignment
{
    /** @return array{before: list<int>, after: list<int>} */
    public static function set(User $user, array $programIds): array
    {
        $programIds = array_values(array_unique(array_map('intval', $programIds)));
        if ($user->role === Role::Coordinator && count($programIds) > 1) {
            throw ValidationException::withMessages([
                'program_ids' => 'A practicum coordinator can be assigned to exactly one program at a time.',
            ]);
        }
        $before = $user->programs()->pluck('programs.id')->map(fn ($id) => (int) $id)->all();
        // Lock affected programs in id order so concurrent changes serialize without deadlocks.
        Program::whereIn('id', array_unique([...$before, ...$programIds]))->orderBy('id')->lockForUpdate()->get();
        $now = now();
        $ending = array_diff($before, $programIds);
        if ($ending !== []) {
            DB::table('coordinator_program')->where('user_id', $user->id)->whereIn('program_id', $ending)
                ->whereNull('removed_at')->update(['removed_at' => $now, 'updated_at' => $now]);
        }
        foreach (array_diff($programIds, $before) as $programId) {
            DB::table('coordinator_program')->insert([
                'user_id' => $user->id, 'program_id' => $programId,
                'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        sort($before);
        sort($programIds);

        return ['before' => $before, 'after' => $programIds];
    }
}
