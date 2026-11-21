<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\PlacementStatus;
use App\Enums\Role;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;

/**
 * Starting and closing a deployment. Start: an approved placement becomes active from its start date.
 * Closing takes two coordinator steps:
 * 1. Certify hours: the coordinator confirms the supervisor-certified total. No log may still be pending,
 *    and after this the student cannot add or change logs and the supervisor cannot review them.
 * 2. End deployment: the placement becomes completed. Chat for that deployment turns read-only
 *    (ChatAccess::writable requires an active placement), so the supervisor can no longer send messages.
 *
 * Scope: a coordinator assigned to the placement's program and term, or an admin.
 */
class PlacementCompletion
{
    /** Start the deployment: an approved placement becomes active on or after its start date. */
    public static function start(User $actor, int $placementId): Placement
    {
        return DB::transaction(function () use ($actor, $placementId) {
            $placement = self::lock($actor, $placementId);
            abort_unless($placement->status === PlacementStatus::Approved, 409, 'Only an approved placement can be started.');
            $today = now(config('internmatch.timezone'))->toDateString();
            abort_if($placement->starts_on === null || $placement->starts_on->toDateString() > $today, 422, 'The deployment cannot start before its start date ('.$placement->starts_on?->toDateString().').');
            abort_if($placement->ends_on !== null && $placement->ends_on->toDateString() < $today, 422, 'The placement period has already ended.');
            $placement->forceFill(['status' => PlacementStatus::Active])->save();
            Audit::record($actor, 'placement.started', $placement, [], $placement->studentEnrollment->programTerm->program_id);
            $body = 'The deployment at '.$placement->hostEstablishment->name.' has started. Time logs and journals can now be submitted.';
            $placement->studentEnrollment->student->user->notify(new PortalNotification('Deployment started', $body));
            $placement->supervisor?->notify(new PortalNotification('Deployment started', $body));

            return $placement;
        });
    }

    public static function certifyHours(User $actor, int $placementId): Placement
    {
        return DB::transaction(function () use ($actor, $placementId) {
            $placement = self::lock($actor, $placementId);
            abort_unless($placement->status === PlacementStatus::Active, 409, 'Only an active deployment can have its hours certified.');
            abort_if($placement->hours_certified_at !== null, 409, 'Hours for this deployment are already certified.');
            $logs = TimeLog::where('placement_id', $placement->id);
            abort_if((clone $logs)->where('status', 'pending')->exists(), 409, 'Some time logs are still pending. The supervisor must certify or return them first.');
            $minutes = (int) (clone $logs)->where('status', 'verified')->sum('credited_minutes');
            $placement->forceFill(['hours_certified_at' => now(), 'hours_certified_by' => $actor->id, 'certified_minutes' => $minutes])->save();
            Audit::record($actor, 'placement.hours_certified', $placement, ['certified_minutes' => $minutes], $placement->studentEnrollment->programTerm->program_id);
            $placement->studentEnrollment->student->user->notify(new PortalNotification('Hours certified', 'Your coordinator certified '.round($minutes / 60, 2).' hours for your deployment.'));

            return $placement;
        });
    }

    public static function end(User $actor, int $placementId): Placement
    {
        return DB::transaction(function () use ($actor, $placementId) {
            $placement = self::lock($actor, $placementId);
            abort_unless($placement->status === PlacementStatus::Active, 409, 'Only an active deployment can be ended.');
            abort_if($placement->hours_certified_at === null, 409, 'Certify the hours before ending the deployment.');
            $required = $placement->studentEnrollment->programTerm->program->required_ojt_hours;
            abort_if($required !== null && $placement->certified_minutes < $required * 60, 422,
                'Certified hours ('.round($placement->certified_minutes / 60, 2).') are below the required '.$required.' hours.');
            $placement->forceFill(['status' => PlacementStatus::Completed, 'completed_at' => now(), 'completed_by' => $actor->id])->save();
            Audit::record($actor, 'placement.completed', $placement, ['certified_minutes' => $placement->certified_minutes], $placement->studentEnrollment->programTerm->program_id);
            $title = 'Deployment ended';
            $body = 'The deployment at '.$placement->hostEstablishment->name.' is complete. Chat for this deployment is now read-only.';
            $placement->studentEnrollment->student->user->notify(new PortalNotification($title, $body));
            $placement->supervisor?->notify(new PortalNotification($title, $body));

            return $placement;
        });
    }

    private static function lock(User $actor, int $placementId): Placement
    {
        abort_unless($actor->status === AccountStatus::Active && in_array($actor->role, [Role::Admin, Role::Coordinator], true), 403);
        $placement = PlacementAccess::query($actor)->findOrFail($placementId);
        abort_if($actor->role === Role::Coordinator && ! $actor->isAssignedToProgramTerm($placement->studentEnrollment->program_term_id), 403);
        StudentEnrollment::whereKey($placement->student_enrollment_id)->lockForUpdate()->firstOrFail();

        return Placement::whereKey($placement->id)->lockForUpdate()->firstOrFail();
    }
}
