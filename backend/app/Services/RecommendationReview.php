<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecommendationReview
{
    public static function explanation(Recommendation $record): string
    {
        $snapshot = $record->snapshot ?? [];

        return implode("\n", [
            'Opportunity: '.($snapshot['opportunity_title'] ?? 'Not recorded'),
            'Host: '.($snapshot['host_name'] ?? 'Not recorded'),
            'Similarity score: '.number_format($record->similarity_score, 2).' (how closely the skills fit the tasks; not a percentage)',
            'Straight-line distance: '.($record->distance_km === null ? 'Unknown' : number_format($record->distance_km, 2).' km'),
            'Available slots at generation: '.$record->capacity_at_time,
            'Agreement status at generation: '.$record->moa_status_at_time,
            'Rank: '.$record->rank,
            'Tasks: '.($snapshot['tasks'] ?? 'Not recorded'),
            'Ranking basis: '.self::rankingBasis($record),
            'Generated: '.$record->generated_at?->toDateTimeString(),
        ]);
    }

    /** Plain-language ranking basis; recommendations saved before 2026-11-22 stored technical wording. */
    public static function rankingBasis(Recommendation $record): string
    {
        $criteria = (string) ($record->snapshot['criteria'] ?? '');
        if (str_starts_with($criteria, 'Ordered by')) {
            return $criteria;
        }

        return GenerateRecommendations::criteria((string) $record->ranking_method, (bool) ($record->snapshot['ranker']['synthetic'] ?? false));
    }

    public static function query(User $actor): Builder
    {
        $query = Recommendation::query();
        if ($actor->role !== Role::Coordinator || ! $actor->hasPermission(Permission::DecidePlacements)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('student_enrollment_id', StudentAccess::enrollments($actor)->select('student_enrollments.id'));
    }

    public static function record(User $actor, Recommendation $recommendation, array $input): int
    {
        abort_unless($actor->role === Role::Coordinator && $actor->hasPermission(Permission::DecidePlacements), 403);
        $data = Validator::make($input, [
            'judgment' => ['required', Rule::in(['suitable', 'unsuitable', 'uncertain'])],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
            'confirm' => ['accepted'],
        ])->validate();

        return DB::transaction(function () use ($actor, $recommendation, $data) {
            $record = self::query($actor)->lockForUpdate()->findOrFail($recommendation->id);
            $id = DB::table('placement_judgments')->insertGetId([
                'recommendation_id' => $record->id, 'coordinator_id' => $actor->id,
                'judgment' => $data['judgment'], 'reason' => $data['reason'],
                'approved_by' => $actor->id, 'approved_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            Audit::record($actor, 'recommendation.judgment_recorded', $record,
                ['judgment_id' => $id, 'judgment' => $data['judgment'], 'reason' => $data['reason']],
                $record->studentEnrollment->programTerm->program_id);

            return $id;
        });
    }
}
