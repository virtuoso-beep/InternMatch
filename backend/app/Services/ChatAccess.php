<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ChatAccess
{
    public static function enabled(): bool
    {
        return (bool) DB::table('chat_settings')->where('id', 1)->value('enabled');
    }

    public static function activePlacement(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'active'])->whereNull('completed_at')
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today(config('internmatch.timezone', 'Asia/Manila'))));
    }

    public static function scope(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->trashed() || $user->status->value !== 'active' || ! in_array($user->role, [Role::Student, Role::Coordinator, Role::Supervisor], true)) {
            return $query->whereRaw('1=0');
        }

        return match ($user->role) {
            Role::Student => $query->whereHas('enrollment.student', fn ($q) => $q->where('user_id', $user->id)),
            Role::Coordinator => $query->whereHas('enrollment.programTerm', fn ($q) => $q->whereIn('program_id', $user->programs()->select('programs.id'))),
            Role::Supervisor => $query->whereHas('placement', fn ($q) => self::activePlacement($q)->where('supervisor_id', $user->id)->whereIn('host_establishment_id', $user->hostEstablishments()->select('host_establishments.id'))),
            default => $query->whereRaw('1=0'),
        };
    }

    public static function coordinator(StudentEnrollment $enrollment): ?User
    {
        $users = $enrollment->programTerm->program->coordinators
            ->filter(fn ($user) => $user->role === Role::Coordinator && $user->status->value === 'active');

        return $users->count() === 1 ? $users->first() : null;
    }

    public static function writable(Conversation $conversation): bool
    {
        if (! self::enabled() || ! self::coordinator($conversation->enrollment)) {
            return false;
        }
        if ($conversation->placement_id) {
            return self::activePlacement(Placement::whereKey($conversation->placement_id))->exists();
        }

        return $conversation->enrollment->status->value === 'enrolled';
    }

    public static function ensure(StudentEnrollment $enrollment, ?Placement $placement = null): void
    {
        if ($placement && ! in_array($placement->status->value, ['approved', 'active', 'completed'], true)) {
            return;
        }
        $key = $placement ? 'placement:'.$placement->id : 'enrollment:'.$enrollment->id;
        DB::table('conversations')->insertOrIgnore(['context_key' => $key, 'student_enrollment_id' => $enrollment->id, 'placement_id' => $placement?->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public static function participants(Conversation $conversation): array
    {
        $users = [$conversation->enrollment->student->user, self::coordinator($conversation->enrollment)];
        if ($conversation->placement_id && self::activePlacement(Placement::whereKey($conversation->placement_id))->exists()) {
            $users[] = $conversation->placement->supervisor;
        }

        return array_values(array_filter($users, fn ($u) => $u && $u->status->value === 'active'));
    }
}
