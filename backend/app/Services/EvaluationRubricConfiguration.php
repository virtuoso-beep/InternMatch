<?php

namespace App\Services;

use App\Models\EvaluationRubric;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Creates a new versioned rubric for a program and makes it the program's current rubric (audited). */
class EvaluationRubricConfiguration
{
    /** @param array{name: string, approval_reference: string, criteria: list<array{name: string, max_score: float|int, weight: float|int}>} $data */
    public static function configure(User $actor, Program $program, array $data): EvaluationRubric
    {
        return DB::transaction(function () use ($actor, $program, $data) {
            $record = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $code = 'PROGRAM-'.$program->id;
            $rubric = EvaluationRubric::create(['code' => $code, 'name' => $data['name'], 'version' => EvaluationRubric::where('code', $code)->max('version') + 1]);
            foreach ($data['criteria'] as $order => $criterion) {
                $rubric->criteria()->create([...$criterion, 'sort_order' => $order]);
            }
            $record->update(['evaluation_rubric_id' => $rubric->id]);
            Audit::record($actor, 'evaluation_rubric.configured', $rubric, $data, $program->id);

            return $rubric;
        });
    }
}
