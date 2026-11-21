<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\EventAttendance;
use App\Models\Placement;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;

/**
 * Rule-based progress monitoring (FR-14, spec §13). Predefined conditions, not predictions.
 *
 * Spec §13 adopted defaults (active for every program; a program term may override any value through
 * `monitoring_rules`). Working days are Monday–Friday minus `internmatch.holidays`.
 * Each condition lists who is notified (`notify`), following the spec §13 rules table.
 */
class ProgressSummary
{
    public const DEFAULTS = [
        'behind_schedule_percent' => 85,      // rule 1: C < 85% of expected hours
        'behind_schedule_after_days' => 10,   // rule 1: only after the first 10 working days
        'max_daily_hours' => 8,               // rule 2: D_max
        'no_log_days' => 5,                   // rule 3
        'uncertified_days' => 5,              // rule 4
        'weekly_report_grace_days' => 3,      // rule 5
        'requirement_grace_days' => 2,        // rule 6
        'evaluation_window_days' => 10,       // rule 7: an evaluation period stays open 10 working days
        'evaluation_grace_days' => 5,         // rule 7: flag 5 working days after it closes
    ];

    public function forPlacement(Placement $placement): array
    {
        $enrollment = $placement->studentEnrollment;
        $term = $enrollment->programTerm;
        $hours = $term->program->required_ojt_hours;
        // Aggregate certified work across reassignments within the same enrollment.
        $logs = TimeLog::whereHas('placement', fn ($query) => $query->where('student_enrollment_id', $enrollment->id));
        $completed = (int) (clone $logs)->where('status', 'verified')->sum('credited_minutes');
        $remaining = $hours === null ? null : max(0, $hours * 60 - $completed);
        $zone = config('internmatch.timezone');
        $today = CarbonImmutable::now($zone)->startOfDay();
        $ends = $placement->ends_on ? CarbonImmutable::parse($placement->ends_on->toDateString(), $zone) : null;
        $starts = $placement->starts_on ? CarbonImmutable::parse($placement->starts_on->toDateString(), $zone) : null;
        $days = $ends ? max(0, (int) $today->diffInDays($ends, false)) : null;
        $stored = $term->monitoring_rules ?? [];
        $rules = array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        $legacy = isset($stored['remaining_days_threshold'], $stored['remaining_hours_threshold']);
        $flags = [];

        // Legacy department threshold rule, kept for terms that configured it.
        if ($legacy && $remaining !== null && $days !== null && $remaining > 0
            && $days <= $stored['remaining_days_threshold'] && $remaining >= $stored['remaining_hours_threshold'] * 60) {
            $flags[] = ['code' => 'insufficient_hours', 'severity' => 'warning', 'notify' => ['student', 'coordinator', 'supervisor'], 'description' => 'Remaining certified internship hours meet the program attention threshold within the remaining period.'];
        }

        $deployed = $placement->status->value === 'active' && $starts && $ends && $starts->lte($today);
        if ($deployed && $hours !== null) {
            $afterEnd = $ends->addDay();
            $total = self::workingDays($starts, $afterEnd);
            $done = self::workingDays($starts, $today->lt($afterEnd) ? $today : $afterEnd);
            $left = $today->lt($afterEnd) ? self::workingDays($today, $afterEnd) : 0;
            $certified = $completed / 60;
            $expected = $total > 0 ? $hours * $done / $total : 0;
            // Rule 1 — Behind schedule.
            if ($done > $rules['behind_schedule_after_days'] && $certified < $expected * $rules['behind_schedule_percent'] / 100) {
                $flags[] = ['code' => 'behind_schedule', 'severity' => 'warning', 'notify' => ['student', 'coordinator'], 'description' => sprintf(
                    'Behind schedule: %.1f certified hours against %.1f expected by today (below %d%% of the expected pace).',
                    $certified, $expected, $rules['behind_schedule_percent'])];
            }
            // Rule 2 — Cannot finish on time.
            if ($hours - $certified > $left * $rules['max_daily_hours']) {
                $flags[] = ['code' => 'cannot_finish', 'severity' => 'critical', 'notify' => ['student', 'coordinator', 'supervisor'], 'description' => sprintf(
                    'Cannot finish on time: %.1f hours remain but at most %d can be logged in the %d remaining working days.',
                    $hours - $certified, $left * $rules['max_daily_hours'], $left)];
            }
            // Rule 3 — No hours logged for N consecutive working days (today may still be logged).
            if ($today->lte($ends)) {
                $last = (clone $logs)->max('work_date');
                $reference = $last ? CarbonImmutable::parse($last, $zone)->startOfDay() : $starts->subDay();
                if ($reference->lt($starts->subDay())) {
                    $reference = $starts->subDay();
                }
                if (self::workingDays($reference->addDay(), $today) >= $rules['no_log_days']) {
                    $flags[] = ['code' => 'no_hours_logged', 'severity' => 'warning', 'notify' => ['student', 'coordinator'], 'description' => sprintf(
                        'No hours logged for %d or more consecutive working days.', $rules['no_log_days'])];
                }
            }
            // Rule 4 — Hours not certified within N working days.
            $oldestPending = (clone $logs)->where('status', 'pending')->min('created_at');
            $waiting = $oldestPending ? self::workingDays(CarbonImmutable::parse($oldestPending)->setTimezone($zone)->startOfDay()->addDay(), $today) : 0;
            if ($oldestPending && $waiting > $rules['uncertified_days']) {
                // Spec: the supervisor first; the coordinator is added after 10 working days.
                $flags[] = ['code' => 'hours_not_certified', 'severity' => 'warning', 'notify' => $waiting > 10 ? ['supervisor', 'coordinator'] : ['supervisor'], 'description' => sprintf(
                    'A time log has waited more than %d working days for supervisor certification.', $rules['uncertified_days'])];
            }
            // Rule 5 — Weekly report missing for a completed week, after the grace period.
            $missing = 0;
            $submitted = $placement->journalEntries()->where('status', '!=', 'draft')->pluck('week_starts_on')
                ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())->all();
            for ($week = $starts->startOfWeek(); $week->lte($today); $week = $week->addWeek()) {
                $weekEnd = $week->endOfWeek()->startOfDay();
                if ($weekEnd->gt($ends)) {
                    break;
                }
                if (self::workingDays($weekEnd->addDay(), $today) >= $rules['weekly_report_grace_days'] && ! in_array($week->toDateString(), $submitted, true)) {
                    $missing++;
                }
            }
            // Rule 7 — Evaluation pending. Midterm opens when half the required hours are certified; final at
            // completion (all hours certified, or the planned end date if earlier). Each period is open for
            // `evaluation_window_days` and is flagged `evaluation_grace_days` after it closes without a submitted evaluation.
            $opened = [];
            $running = 0;
            foreach ((clone $logs)->where('status', 'verified')->orderBy('work_date')->get(['work_date', 'credited_minutes']) as $log) {
                $running += (int) $log->credited_minutes;
                $date = CarbonImmutable::parse($log->work_date, $zone)->startOfDay();
                if (! isset($opened['midterm']) && $running >= $hours * 30) {
                    $opened['midterm'] = $date;
                }
                if (! isset($opened['final']) && $running >= $hours * 60) {
                    $opened['final'] = $date;
                }
            }
            if ($ends->lte($today) && (! isset($opened['final']) || $ends->lt($opened['final']))) {
                $opened['final'] = $ends;
            }
            $pendingPeriods = [];
            foreach ($opened as $period => $from) {
                $overdue = self::workingDays($from, $today) >= $rules['evaluation_window_days'] + $rules['evaluation_grace_days'];
                $submittedEvaluation = $placement->evaluations()->where('status', 'submitted')->where('period', 'like', '%'.$period.'%')->exists();
                if ($overdue && ! $submittedEvaluation) {
                    $pendingPeriods[] = $period;
                }
            }
            if ($pendingPeriods !== []) {
                $flags[] = ['code' => 'evaluation_pending', 'severity' => 'warning', 'notify' => ['supervisor', 'coordinator'], 'description' => sprintf(
                    'Supervisor evaluation not submitted for the %s period%s after it closed.', implode(' and ', $pendingPeriods), count($pendingPeriods) === 1 ? '' : 's')];
            }
            if ($missing > 0) {
                $flags[] = ['code' => 'weekly_report_missing', 'severity' => 'warning', 'notify' => ['student', 'coordinator'], 'description' => sprintf(
                    'Weekly accomplishment report missing for %d completed week%s.', $missing, $missing === 1 ? '' : 's')];
            }
        }

        // Rule 6 — Requirement incomplete N working days after its deadline.
        // Lists preload requirements (with their types) to avoid one query per row; same filter either way.
        $overdue = $term->relationLoaded('requirements')
            ? $term->requirements->filter(fn ($r) => $r->is_required && $r->due_at !== null && $r->due_at->lt(now()))->values()
            : $term->requirements()->where('is_required', true)->whereNotNull('due_at')->where('due_at', '<', now())->with('requirementType')->get();
        foreach ($overdue as $requirement) {
            $due = CarbonImmutable::parse($requirement->due_at)->setTimezone($zone)->startOfDay();
            if (self::workingDays($due->addDay(), $today) < $rules['requirement_grace_days']) {
                continue;
            }
            if ($requirement->requirementType->kind === 'event') {
                if (! EventAttendance::where('student_enrollment_id', $enrollment->id)->where('program_term_requirement_id', $requirement->id)->whereNotNull('confirmed_at')->exists()) {
                    $flags[] = ['code' => 'requirement_'.$requirement->id, 'severity' => 'warning', 'notify' => ['student', 'coordinator'], 'description' => $requirement->requirementType->name.' is overdue and attendance has not been confirmed.'];
                }

                continue;
            }
            $latest = $enrollment->requirementSubmissions()->where('program_term_requirement_id', $requirement->id)->orderByDesc('revision')->first();
            if (! $latest || $latest->status !== SubmissionStatus::Approved) {
                $flags[] = ['code' => 'requirement_'.$requirement->id, 'severity' => 'warning', 'notify' => ['student', 'coordinator'], 'description' => $requirement->requirementType->name.' is overdue and has no approved latest submission.'];
            }
        }

        return ['required_hours' => $hours, 'completed_minutes' => $completed, 'remaining_minutes' => $remaining,
            'remaining_days' => $days,
            // Spec §13 defaults are always active, so hour rules are configured whenever required hours are known.
            'hours_rule_configured' => $hours !== null, 'rules' => $rules, 'flags' => $flags,
            'method' => 'Predefined program rules (spec §13 defaults); not a prediction.'];
    }

    /** Monday–Friday days in [$from, $to), excluding configured holidays (`internmatch.holidays`, Y-m-d). */
    public static function workingDays(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $holidays = array_flip(config('internmatch.holidays', []));
        $count = 0;
        for ($day = $from->startOfDay(); $day->lt($to); $day = $day->addDay()) {
            if (! $day->isWeekend() && ! isset($holidays[$day->toDateString()])) {
                $count++;
            }
        }

        return $count;
    }
}
