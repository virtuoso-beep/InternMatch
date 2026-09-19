<?php

namespace App\Http\Controllers;

use App\Enums\PlacementStatus;
use App\Models\Evaluation;
use App\Models\EvaluationRubric;
use App\Models\StudentEnrollment;
use App\Notifications\PortalNotification;
use App\Services\Audit;
use App\Services\EvaluationScoring;
use App\Services\PlacementAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(Request $request, int $placement, EvaluationScoring $scoring)
    {
        $record = PlacementAccess::query($request->user())->findOrFail($placement);
        $evaluations = $record->evaluations()->where(fn ($query) => $query->where('status', 'submitted')->orWhere('evaluator_id', $request->user()->id))
            ->with(['evaluationRubric.criteria', 'scores', 'evaluator:id,name'])->orderBy('period')->get();
        foreach ($evaluations as $evaluation) {
            $evaluation->setAttribute('weighted_percentage', $scoring->percentage($evaluation));
        }

        return response()->json(['data' => $evaluations, 'rubric' => EvaluationRubric::with('criteria')->find($record->studentEnrollment->programTerm->program->evaluation_rubric_id)], headers: ['Cache-Control' => 'no-store']);
    }

    public function save(Request $request, int $placement, EvaluationScoring $scoring)
    {
        $record = PlacementAccess::query($request->user())->findOrFail($placement);
        Gate::authorize('create', [Evaluation::class, $record]);
        abort_unless(in_array($record->status, [PlacementStatus::Active, PlacementStatus::Completed], true), 422, 'The internship must be active or completed.');
        $data = $request->validate([
            'period' => ['required', 'string', 'max:60'], 'status' => ['required', Rule::in(['draft', 'submitted'])],
            'comments' => ['nullable', 'string', 'max:10000'], 'scores' => ['required', 'array', 'min:1', 'max:100'],
            'scores.*.criterion_id' => ['required', 'integer', 'distinct'], 'scores.*.score' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'scores.*.comments' => ['nullable', 'string', 'max:3000'],
        ]);
        $evaluation = DB::transaction(function () use ($request, $record, $data) {
            StudentEnrollment::whereKey($record->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $evaluation = $record->evaluations()->where('period', $data['period'])->first();
            if ($evaluation) {
                Gate::authorize('update', $evaluation);
            } else {
                $rubric = EvaluationRubric::with('criteria')->where('is_active', true)->find($record->studentEnrollment->programTerm->program->evaluation_rubric_id);
                abort_unless($rubric, 422, 'No confirmed program evaluation rubric has been configured.');
                $evaluation = new Evaluation(['placement_id' => $record->id, 'evaluation_rubric_id' => $rubric->id, 'evaluator_id' => $request->user()->id, 'period' => $data['period']]);
            }
            $criteria = $evaluation->evaluationRubric->criteria->keyBy('id');
            foreach ($data['scores'] as $score) {
                $criterion = $criteria->get($score['criterion_id']);
                if (! $criterion || $score['score'] > (float) $criterion->max_score) {
                    throw ValidationException::withMessages(['scores' => 'Every score must belong to the assigned rubric and be within its scale.']);
                }
            }
            if ($data['status'] === 'submitted' && count($data['scores']) !== $criteria->count()) {
                throw ValidationException::withMessages(['scores' => 'Score every rubric criterion before submitting.']);
            }
            $evaluation->fill(['status' => $data['status'], 'comments' => $data['comments'] ?? null, 'submitted_at' => $data['status'] === 'submitted' ? now() : null])->save();
            $evaluation->scores()->delete();
            foreach ($data['scores'] as $score) {
                $evaluation->scores()->create(['evaluation_rubric_id' => $evaluation->evaluation_rubric_id, 'evaluation_criterion_id' => $score['criterion_id'], 'score' => $score['score'], 'comments' => $score['comments'] ?? null]);
            }
            Audit::record($request->user(), 'evaluation.saved', $evaluation, ['status' => $data['status'], 'rubric_id' => $evaluation->evaluation_rubric_id], $record->studentEnrollment->programTerm->program_id);
            if ($data['status'] === 'submitted') {
                $record->studentEnrollment->student->user->notify(new PortalNotification('Supervisor evaluation submitted', 'Your '.$data['period'].' evaluation has been submitted.'));
            }

            return $evaluation->load(['scores', 'evaluationRubric.criteria']);
        });
        $evaluation->setAttribute('weighted_percentage', $scoring->percentage($evaluation));

        return response()->json(['data' => $evaluation]);
    }
}
