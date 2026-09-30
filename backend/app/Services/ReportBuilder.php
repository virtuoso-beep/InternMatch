<?php

namespace App\Services;

use App\Models\{Evaluation, EventAttendance, Placement, ProgramTerm, Recommendation, StudentEnrollment, TimeLog};
use Illuminate\Support\Facades\DB;

class ReportBuilder
{
    public const KINDS = ['placement', 'progress', 'hours', 'requirements', 'evaluation', 'recommendation', 'summary'];

    public function build(ProgramTerm $term, string $kind): array
    {
        $enrollments = StudentEnrollment::where('program_term_id', $term->id)->with('student.user')->orderBy('id')->get();
        $placements = Placement::whereIn('student_enrollment_id', $enrollments->pluck('id'))->with(['hostEstablishment', 'opportunity', 'supervisor'])->orderBy('id')->get();
        $names = $enrollments->mapWithKeys(fn ($e) => [$e->id => $e->student->user->name]);
        $rows = [];
        switch ($kind) {
            case 'placement':
                $columns = ['placement_id', 'enrollment_id', 'student', 'host', 'opportunity', 'status', 'supervisor', 'starts_on', 'ends_on'];
                foreach ($placements as $p) { $rows[] = [$p->id, $p->student_enrollment_id, $names[$p->student_enrollment_id], $p->hostEstablishment->name, $p->opportunity->title, $p->status->value, $p->supervisor?->name, $p->starts_on?->toDateString(), $p->ends_on?->toDateString()]; }
                break;
            case 'progress':
                $columns = ['enrollment_id', 'student', 'enrollment_status', 'placement_status', 'required_hours', 'certified_hours', 'remaining_hours', 'attention_conditions'];
                foreach ($enrollments as $e) {
                    $p = $placements->where('student_enrollment_id', $e->id)->last();
                    $minutes = (int) TimeLog::whereHas('placement', fn ($q) => $q->where('student_enrollment_id', $e->id))->where('status', 'verified')->sum('credited_minutes');
                    $hours = $term->program->required_ojt_hours;
                    $flags = $p && in_array($p->status->value, ['approved', 'active'], true) ? app(ProgressSummary::class)->forPlacement($p)['flags'] : [];
                    $rows[] = [$e->id, $names[$e->id], $e->status->value, $p?->status->value ?? 'unplaced', $hours, round($minutes / 60, 2), $hours === null ? null : max(0, round($hours - $minutes / 60, 2)), implode('; ', array_column($flags, 'description'))];
                }
                break;
            case 'hours':
                $columns = ['placement_id', 'student', 'work_date', 'time_in_utc', 'time_out_utc', 'status', 'certified_minutes'];
                foreach (TimeLog::whereIn('placement_id', $placements->pluck('id'))->orderBy('work_date')->orderBy('id')->get() as $log) {
                    $p = $placements->firstWhere('id', $log->placement_id);
                    $rows[] = [$p->id, $names[$p->student_enrollment_id], $log->work_date->toDateString(), $log->time_in?->toIso8601String(), $log->time_out?->toIso8601String(), $log->status->value, $log->status->value === 'verified' ? $log->credited_minutes : null];
                }
                break;
            case 'requirements':
                $columns = ['enrollment_id', 'student', 'requirement', 'kind', 'required', 'status', 'revision_or_attended_on'];
                foreach ($enrollments as $e) {
                    foreach ($term->requirements()->with('requirementType')->get() as $r) {
                        if ($r->requirementType->kind === 'event') {
                            $a = EventAttendance::where('student_enrollment_id', $e->id)->where('program_term_requirement_id', $r->id)->first();
                            $status = $a?->confirmed_at ? 'confirmed' : ($a ? 'awaiting_confirmation' : 'not_attended'); $detail = $a?->attended_on?->toDateString();
                        } else {
                            $s = $e->requirementSubmissions()->where('program_term_requirement_id', $r->id)->latest('revision')->first();
                            $status = $s?->status->value ?? 'not_submitted'; $detail = $s?->revision;
                        }
                        $rows[] = [$e->id, $names[$e->id], $r->requirementType->name, $r->requirementType->kind, $r->is_required ? 'yes' : 'no', $status, $detail];
                    }
                }
                break;
            case 'evaluation':
                $columns = ['evaluation_id', 'student', 'host', 'period', 'rubric', 'rubric_version', 'weighted_percentage', 'submitted_at'];
                foreach (Evaluation::whereIn('placement_id', $placements->pluck('id'))->where('status', 'submitted')->with(['evaluationRubric.criteria', 'scores'])->orderBy('id')->get() as $evaluation) {
                    $p = $placements->firstWhere('id', $evaluation->placement_id);
                    $rows[] = [$evaluation->id, $names[$p->student_enrollment_id], $p->hostEstablishment->name, $evaluation->period, $evaluation->evaluationRubric->name, $evaluation->evaluationRubric->version, app(EvaluationScoring::class)->percentage($evaluation), $evaluation->submitted_at?->toIso8601String()];
                }
                break;
            case 'recommendation':
                $columns = ['recommendation_id', 'student', 'host_at_generation', 'opportunity_at_generation', 'similarity', 'straight_line_km', 'capacity_at_generation', 'moa_at_generation', 'model', 'model_version', 'generated_at'];
                foreach ($enrollments as $e) {
                    $generation = DB::table('recommendation_generations')->where('student_enrollment_id', $e->id)->orderByDesc('id')->value('generation_id');
                    if (! $generation) { continue; }
                    foreach (Recommendation::where('generation_id', $generation)->where('student_enrollment_id', $e->id)->orderBy('rank')->get() as $r) {
                        $rows[] = [$r->id, $names[$e->id], $r->snapshot['host_name'] ?? null, $r->snapshot['opportunity_title'] ?? null, $r->similarity_score, $r->distance_km, $r->capacity_at_time, $r->moa_status_at_time, $r->snapshot['model_name'] ?? null, $r->snapshot['model_version'] ?? null, $r->generated_at->toIso8601String()];
                    }
                }
                break;
            case 'summary':
                $columns = ['program', 'term', 'enrollments', 'students_with_current_or_completed_placement', 'approved', 'active', 'completed', 'required_ojt_hours'];
                $rows[] = [$term->program->code, $term->academicTerm->name, $enrollments->count(), $placements->whereIn('status', [\App\Enums\PlacementStatus::Approved, \App\Enums\PlacementStatus::Active, \App\Enums\PlacementStatus::Completed])->pluck('student_enrollment_id')->unique()->count(), $placements->where('status', \App\Enums\PlacementStatus::Approved)->count(), $placements->where('status', \App\Enums\PlacementStatus::Active)->count(), $placements->where('status', \App\Enums\PlacementStatus::Completed)->count(), $term->program->required_ojt_hours];
                break;
            default: abort(422, 'Unsupported report kind.');
        }

        return ['program' => $term->program->code, 'term' => $term->academicTerm->name, 'generated_at' => now()->toIso8601String(), 'columns' => $columns, 'rows' => $rows];
    }
}
