<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Archive, restore and delete for hosts, MOAs, opportunities and academic terms.
 *
 * Records with history are archived, never deleted: an archived record stays readable and keeps its links,
 * but it is hidden from default lists and is not eligible for new recommendations or placements
 * (OpportunityEligibility). A record may be deleted only when nothing has ever referenced it.
 *
 * Scope: admin acts on every record. A coordinator acts only on records whose programs are all inside the
 * coordinator's assigned programs, so one program cannot archive a record another program depends on.
 * Academic terms are admin-only. Supervisors, deans and students cannot archive, restore or delete.
 */
class RecordArchive
{
    /** Placements that still depend on a record. */
    private const LIVE_PLACEMENT = ['pending', 'approved', 'active'];

    public static function resolve(User $actor, string $type, int $id): Model
    {
        abort_unless($actor->status === AccountStatus::Active, 403);

        return match ($type) {
            'host' => self::host($actor, $id),
            'moa' => self::moa($actor, $id),
            'opportunity' => self::opportunity($actor, $id),
            'term' => self::term($actor, $id),
        };
    }

    public static function archive(User $actor, string $type, int $id): Model
    {
        return DB::transaction(function () use ($actor, $type, $id) {
            $record = self::resolve($actor, $type, $id);
            $record = $record::query()->lockForUpdate()->findOrFail($record->getKey());
            abort_if($record->archived_at !== null, 409, 'This record is already archived.');
            self::assertNotLive($type, $record);
            $record->forceFill(['archived_at' => now(), 'archived_by' => $actor->id])->save();
            Audit::record($actor, $type.'.archived', $record, []);

            return $record;
        });
    }

    public static function restore(User $actor, string $type, int $id): Model
    {
        return DB::transaction(function () use ($actor, $type, $id) {
            $record = self::resolve($actor, $type, $id);
            $record = $record::query()->lockForUpdate()->findOrFail($record->getKey());
            abort_if($record->archived_at === null, 409, 'This record is not archived.');
            $record->forceFill(['archived_at' => null, 'archived_by' => null])->save();
            Audit::record($actor, $type.'.restored', $record, []);

            return $record;
        });
    }

    /** Deletes a record that has never been used. Anything with history must be archived instead. */
    public static function delete(User $actor, string $type, int $id): void
    {
        $message = 'This record has history and cannot be deleted. Archive it instead.';
        try {
            DB::transaction(function () use ($actor, $type, $id, $message) {
                $record = self::resolve($actor, $type, $id);
                $record = $record::query()->lockForUpdate()->findOrFail($record->getKey());
                abort_if(self::used($type, $record), 409, $message);
                // Configuration rows that only describe the record itself go with it.
                match ($type) {
                    'host' => DB::table('host_program_capacity')->where('host_establishment_id', $record->id)->delete(),
                    'moa' => DB::table('moa_program')->where('moa_id', $record->id)->delete(),
                    'opportunity' => [
                        DB::table('opportunity_program')->where('opportunity_id', $record->id)->delete(),
                        DB::table('opportunity_competency')->where('opportunity_id', $record->id)->delete(),
                        DB::table('embeddings')->where('owner_type', $record->getMorphClass())->where('owner_id', $record->id)->delete(),
                    ],
                    'term' => null,
                };
                Audit::record($actor, $type.'.deleted', $record, ['label' => self::label($type, $record)]);
                $record->delete();
            });
        } catch (QueryException $exception) {
            // A reference this check does not know about (foreign key restrict): treat it as history.
            abort(409, $message);
        }
    }

    public static function label(string $type, Model $record): string
    {
        return match ($type) {
            'host' => $record->name,
            'moa' => $record->reference_number,
            'opportunity' => $record->title,
            'term' => $record->name,
        };
    }

    private static function host(User $actor, int $id): HostEstablishment
    {
        abort_unless(in_array($actor->role, [Role::Admin, Role::Coordinator], true), 403);
        $host = HostAccess::query($actor)->findOrFail($id);
        if ($actor->role === Role::Coordinator) {
            $programs = DB::table('host_program_capacity')->where('host_establishment_id', $host->id)->pluck('program_id')
                ->merge(DB::table('opportunity_program')->whereIn('opportunity_id', Opportunity::where('host_establishment_id', $host->id)->select('id'))->pluck('program_id'))
                ->merge(DB::table('moa_program')->whereIn('moa_id', Moa::where('host_establishment_id', $host->id)->select('id'))->pluck('program_id'))
                ->unique()->values()->all();
            HostAccess::authorizePrograms($actor, $programs);
        }

        return $host;
    }

    private static function moa(User $actor, int $id): Moa
    {
        abort_unless(in_array($actor->role, [Role::Admin, Role::Coordinator], true), 403);
        $query = Moa::whereIn('host_establishment_id', HostAccess::query($actor)->select('id'));
        if ($actor->role === Role::Coordinator) {
            $query->whereHas('programs', fn ($programs) => $programs->whereIn('programs.id', $actor->programs()->select('programs.id')));
        }
        $moa = $query->findOrFail($id);
        if ($actor->role === Role::Coordinator) {
            HostAccess::authorizePrograms($actor, $moa->programs()->pluck('programs.id')->all());
        }

        return $moa;
    }

    private static function opportunity(User $actor, int $id): Opportunity
    {
        abort_unless(in_array($actor->role, [Role::Admin, Role::Coordinator], true), 403);
        $opportunity = OpportunityManagement::query($actor)->findOrFail($id);
        if ($actor->role === Role::Coordinator) {
            HostAccess::authorizePrograms($actor, $opportunity->programs()->pluck('programs.id')->all());
        }

        return $opportunity;
    }

    private static function term(User $actor, int $id): AcademicTerm
    {
        abort_unless($actor->hasPermission(Permission::ManageAcademicRecords), 403);

        return AcademicTerm::findOrFail($id);
    }

    private static function assertNotLive(string $type, Model $record): void
    {
        $live = Placement::query()->whereIn('status', self::LIVE_PLACEMENT);
        match ($type) {
            'host' => abort_if((clone $live)->where('host_establishment_id', $record->id)->exists(), 409, 'This host has pending, approved or active placements. End or move them before archiving.'),
            'moa' => abort_if((clone $live)->where('moa_id', $record->id)->exists(), 409, 'Placements still rely on this agreement. End or move them before archiving.'),
            'opportunity' => abort_if((clone $live)->where('opportunity_id', $record->id)->exists(), 409, 'This opportunity has pending, approved or active placements. End or move them before archiving.'),
            'term' => [
                abort_if($record->is_active, 409, 'Deactivate this term before archiving it.'),
                abort_if((clone $live)->whereHas('studentEnrollment.programTerm', fn ($query) => $query->where('academic_term_id', $record->id))->exists(), 409, 'This term has pending, approved or active placements.'),
            ],
        };
    }

    private static function used(string $type, Model $record): bool
    {
        return match ($type) {
            'host' => Placement::where('host_establishment_id', $record->id)->exists()
                || Opportunity::where('host_establishment_id', $record->id)->exists()
                || Moa::where('host_establishment_id', $record->id)->exists(),
            'moa' => Placement::where('moa_id', $record->id)->exists() || $record->document_id !== null,
            'opportunity' => Placement::where('opportunity_id', $record->id)->exists()
                || DB::table('recommendations')->where('opportunity_id', $record->id)->exists()
                || DB::table('opportunity_interests')->where('opportunity_id', $record->id)->exists(),
            'term' => DB::table('program_terms')->where('academic_term_id', $record->id)->exists()
                || Opportunity::where('academic_term_id', $record->id)->exists()
                || DB::table('host_program_capacity')->where('academic_term_id', $record->id)->exists(),
        };
    }
}
