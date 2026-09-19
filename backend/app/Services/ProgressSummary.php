<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Placement;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;

class ProgressSummary
{
    public function forPlacement(Placement $placement): array
    {
        $enrollment = $placement->studentEnrollment;
        $term = $enrollment->programTerm;
        $hours = $term->program->required_ojt_hours;
        // Aggregate certified work across reassignments within the same enrollment.
        $completed = (int) TimeLog::whereHas('placement', fn ($query) => $query->where('student_enrollment_id', $enrollment->id))->where('status', 'verified')->sum('credited_minutes');
        $remaining = $hours === null ? null : max(0, $hours * 60 - $completed);
        $today = CarbonImmutable::now(config('internmatch.timezone'))->startOfDay();
        $ends = $placement->ends_on ? CarbonImmutable::parse($placement->ends_on->toDateString(), config('internmatch.timezone')) : null;
        $days = $ends ? max(0, (int) $today->diffInDays($ends, false)) : null;
        $rules = $term->monitoring_rules ?? [];
        $configured = isset($rules['remaining_days_threshold'], $rules['remaining_hours_threshold']);
        $flags = [];
        if ($remaining !== null && $days !== null && $configured && $remaining > 0
            && $days <= $rules['remaining_days_threshold'] && $remaining >= $rules['remaining_hours_threshold'] * 60) {
            $flags[] = ['code' => 'insufficient_hours', 'description' => 'Remaining certified internship hours meet the program attention threshold within the remaining period.'];
        }
        foreach ($term->requirements()->where('is_required', true)->whereNotNull('due_at')->where('due_at', '<', now())->with('requirementType')->get() as $requirement) {
            $latest = $enrollment->requirementSubmissions()->where('program_term_requirement_id', $requirement->id)->orderByDesc('revision')->first();
            if (! $latest || $latest->status !== SubmissionStatus::Approved) {
                $flags[] = ['code' => 'requirement_'.$requirement->id, 'description' => $requirement->requirementType->name.' is overdue and has no approved latest submission.'];
            }
        }

        return ['required_hours' => $hours, 'completed_minutes' => $completed, 'remaining_minutes' => $remaining,
            'remaining_days' => $days, 'hours_rule_configured' => $configured, 'rules' => $rules, 'flags' => $flags,
            'method' => 'Predefined program rules; not a prediction.'];
    }
}
