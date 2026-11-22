<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    /** Placements whose conversation the assigned supervisor may read: current or ended, not cancelled. */
    public static function supervisedPlacement(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'active', 'completed']);
    }

    public static function scope(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->trashed() || $user->status->value !== 'active' || ! in_array($user->role, [Role::Student, Role::Coordinator, Role::Supervisor], true)) {
            return $query->whereRaw('1=0');
        }

        return match ($user->role) {
            Role::Student => $query->whereHas('enrollment.student', fn ($q) => $q->where('user_id', $user->id)),
            Role::Coordinator => $query->whereHas('enrollment.programTerm', fn ($q) => $q->whereIn('program_id', $user->programs()->select('programs.id'))),
            // Spec §17A-A (confirmed 17 Nov 2026): after the deployment ends the conversation is read-only for all
            // participants and its history stays visible to them; sending still requires an active deployment (writable()).
            Role::Supervisor => $query->whereHas('placement', fn ($q) => self::supervisedPlacement($q)->where('supervisor_id', $user->id)->whereIn('host_establishment_id', $user->hostEstablishments()->select('host_establishments.id'))),
            default => $query->whereRaw('1=0'),
        };
    }

    public static function coordinators(StudentEnrollment $enrollment): Collection
    {
        return $enrollment->programTerm->program->coordinators
            ->filter(fn ($user) => $user->role === Role::Coordinator && $user->status->value === 'active');
    }

    /** Same rule as activePlacement(), checked on an already loaded placement (lists avoid one query per row). */
    private static function isActivePlacement(Placement $placement): bool
    {
        return in_array($placement->status->value, ['approved', 'active'], true) && $placement->completed_at === null
            && ($placement->ends_on === null || $placement->ends_on->toDateString() >= today(config('internmatch.timezone', 'Asia/Manila'))->toDateString());
    }

    public static function writable(Conversation $conversation, ?bool $enabled = null): bool
    {
        if (! ($enabled ?? self::enabled()) || self::coordinators($conversation->enrollment)->isEmpty()) {
            return false;
        }
        if ($conversation->placement_id) {
            return $conversation->relationLoaded('placement') && $conversation->placement
                ? self::isActivePlacement($conversation->placement)
                : self::activePlacement(Placement::whereKey($conversation->placement_id))->exists();
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
        $users = [$conversation->enrollment->student->user, ...self::coordinators($conversation->enrollment)->all()];
        $supervised = $conversation->relationLoaded('placement') && $conversation->placement
            ? in_array($conversation->placement->status->value, ['approved', 'active', 'completed'], true)
            : ($conversation->placement_id && self::supervisedPlacement(Placement::whereKey($conversation->placement_id))->exists());
        if ($conversation->placement_id && $supervised) {
            $users[] = $conversation->placement->supervisor;
        }

        return array_values(array_filter($users, fn ($u) => $u && $u->status->value === 'active'));
    }
}
