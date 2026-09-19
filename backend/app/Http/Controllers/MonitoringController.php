<?php

namespace App\Http\Controllers;

use App\Enums\PlacementStatus;
use App\Enums\Role;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Notifications\PortalNotification;
use App\Services\Audit;
use App\Services\PlacementAccess;
use App\Services\ProgressSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()->role === Role::Dean, 403);

        return response()->json(PlacementAccess::query($request->user())->with(['studentEnrollment.student.user:id,name', 'studentEnrollment.programTerm.program:id,code', 'hostEstablishment:id,name', 'opportunity:id,title', 'supervisor:id,name'])->latest()->paginate(30), headers: ['Cache-Control' => 'no-store']);
    }

    public function show(Request $request, int $placement, ProgressSummary $progress)
    {
        $record = PlacementAccess::query($request->user())->findOrFail($placement);

        return response()->json(['placement' => $record, 'time_logs' => $record->timeLogs()->orderByDesc('time_in')->get(), 'journals' => $record->journalEntries()->orderByDesc('week_starts_on')->get(), 'progress' => $progress->forPlacement($record)], headers: ['Cache-Control' => 'no-store']);
    }

    private function ownActive(Request $request, int $placement): Placement
    {
        $record = PlacementAccess::query($request->user())->findOrFail($placement);
        abort_unless($request->user()->role === Role::Student && $record->status === PlacementStatus::Active, 403, 'An active internship is required for submissions.');

        return $record;
    }

    public function time(Request $request, int $placement)
    {
        $record = $this->ownActive($request, $placement);
        $data = $request->validate(['id' => ['nullable', 'integer'], 'time_in' => ['required', 'date'], 'time_out' => ['required', 'date', 'after:time_in', 'before_or_equal:now'], 'break_minutes' => ['required', 'integer', 'min:0'], 'notes' => ['nullable', 'string', 'max:5000'], 'credited_minutes' => ['prohibited'], 'status' => ['prohibited']]);
        $start = CarbonImmutable::parse($data['time_in'])->utc();
        $end = CarbonImmutable::parse($data['time_out'])->utc();
        $duration = (int) $start->diffInMinutes($end);
        $workDate = $start->setTimezone(config('internmatch.timezone'))->toDateString();
        $endDate = $end->setTimezone(config('internmatch.timezone'))->toDateString();
        if ($duration > 1440 || $duration <= $data['break_minutes'] || $workDate < $record->starts_on->toDateString() || ($record->ends_on && $endDate > $record->ends_on->toDateString())) {
            throw ValidationException::withMessages(['time_in' => 'Use a positive work interval within placement dates, at most 24 hours, with breaks shorter than the interval.']);
        }
        $log = DB::transaction(function () use ($request, $record, $data, $start, $end, $workDate) {
            StudentEnrollment::whereKey($record->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $log = isset($data['id']) ? $record->timeLogs()->findOrFail($data['id']) : new TimeLog(['placement_id' => $record->id]);
            abort_if($log->status?->value === 'verified', 409, 'Certified time logs cannot be edited.');
            $overlap = TimeLog::whereHas('placement', fn ($query) => $query->where('student_enrollment_id', $record->student_enrollment_id))
                ->when($log->exists, fn ($query) => $query->where('id', '<>', $log->id))
                ->where('time_in', '<', $end)->where(fn ($query) => $query->whereNull('time_out')->orWhere('time_out', '>', $start))->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['time_in' => 'This interval overlaps another recorded work interval.']);
            }
            $log->fill(['time_in' => $start, 'time_out' => $end, 'work_date' => $workDate, 'break_minutes' => $data['break_minutes'], 'notes' => $data['notes'] ?? null,
                'credited_minutes' => null, 'status' => 'pending', 'verified_by' => null, 'verified_at' => null])->save();
            Audit::record($request->user(), 'time_log.submitted', $log, ['work_date' => $workDate], $record->studentEnrollment->programTerm->program_id);

            return $log;
        });

        return response()->json(['data' => $log], 201);
    }

    public function verify(Request $request, TimeLog $timeLog)
    {
        Gate::authorize('verify', $timeLog);
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'flagged'])], 'notes' => ['required_if:status,flagged', 'nullable', 'string', 'max:5000'], 'credited_minutes' => ['prohibited']]);
        DB::transaction(function () use ($request, $timeLog, $data) {
            StudentEnrollment::whereKey($timeLog->placement->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $log = TimeLog::whereKey($timeLog->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('verify', $log);
            abort_if($log->status->value === 'verified', 409, 'This time log has already been certified.');
            abort_unless($log->time_out, 422, 'A complete time interval is required.');
            $minutes = (int) $log->time_in->diffInMinutes($log->time_out) - $log->break_minutes;
            abort_if($minutes <= 0 || $minutes > 1440, 422, 'The recorded work duration is invalid.');
            $log->update(['status' => $data['status'], 'credited_minutes' => $data['status'] === 'verified' ? $minutes : null, 'verified_by' => $request->user()->id, 'verified_at' => now(), 'notes' => $data['notes'] ?? $log->notes]);
            Audit::record($request->user(), 'time_log.reviewed', $log, ['status' => $data['status'], 'credited_minutes' => $log->credited_minutes], $log->placement->studentEnrollment->programTerm->program_id);
        });

        return response()->json(['data' => $timeLog->fresh()]);
    }

    public function journal(Request $request, int $placement)
    {
        $record = $this->ownActive($request, $placement);
        $data = $request->validate(['week_starts_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'content' => ['required', 'string', 'max:30000'], 'status' => ['required', Rule::in(['draft', 'submitted'])]]);
        $week = CarbonImmutable::parse($data['week_starts_on']);
        abort_unless($week->isMonday() && $week->endOfWeek()->gte($record->starts_on) && (! $record->ends_on || $week->lte($record->ends_on)), 422, 'Choose the Monday of a week within the placement period.');
        $journal = DB::transaction(function () use ($record, $data, $request) {
            StudentEnrollment::whereKey($record->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $entry = $record->journalEntries()->firstOrNew(['week_starts_on' => $data['week_starts_on']]);
            abort_if(in_array($entry->status?->value, ['submitted', 'approved'], true), 409, 'A submitted or approved journal cannot be edited.');
            $entry->fill([...$data, 'submitted_at' => $data['status'] === 'submitted' ? now() : null])->save();
            Audit::record($request->user(), 'journal.saved', $entry, ['status' => $data['status']], $record->studentEnrollment->programTerm->program_id);

            return $entry;
        });

        return response()->json(['data' => $journal]);
    }

    public function reviewJournal(Request $request, JournalEntry $journal)
    {
        $user = $request->user();
        abort_unless($user->role === Role::Coordinator && $user->isAssignedToProgramTerm($journal->placement->studentEnrollment->program_term_id), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required_if:status,rejected', 'nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $journal, $data) {
            StudentEnrollment::whereKey($journal->placement->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $entry = JournalEntry::whereKey($journal->id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->status->value === 'submitted', 409);
            $entry->update(['status' => $data['status']]);
            Audit::record($request->user(), 'journal.reviewed', $entry, $data, $journal->placement->studentEnrollment->programTerm->program_id);
            $journal->placement->studentEnrollment->student->user->notify(new PortalNotification('Journal review saved', 'Journal for '.$entry->week_starts_on->toDateString().': '.$data['status'].'. '.($data['comments'] ?? '')));
        });

        return response()->json(['data' => $journal->fresh()]);
    }
}
