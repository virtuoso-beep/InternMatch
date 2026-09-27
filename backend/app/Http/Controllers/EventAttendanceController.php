<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\Permission;
use App\Models\EventAttendance;
use App\Models\StudentEnrollment;
use App\Notifications\PortalNotification;
use App\Services\Audit;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EventAttendanceController extends Controller
{
    public function attend(Request $request, int $enrollment)
    {
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        Gate::authorize('submitRequirements', $student);
        $data = $request->validate([
            'requirement_id' => ['required', 'integer'],
            'attended_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $attendance = DB::transaction(function () use ($request, $student, $data) {
            $student = StudentEnrollment::whereKey($student->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('submitRequirements', $student);
            $requirement = $student->programTerm->requirements()->findOrFail($data['requirement_id']);
            abort_unless($requirement->requirementType->kind === 'event', 422, 'Document requirements require a file submission.');
            $record = EventAttendance::where('student_enrollment_id', $student->id)
                ->where('program_term_requirement_id', $requirement->id)->first();
            abort_if($record?->confirmed_at !== null, 409, 'Confirmed attendance cannot be changed.');
            $record = EventAttendance::updateOrCreate([
                'student_enrollment_id' => $student->id, 'program_term_requirement_id' => $requirement->id,
            ], ['program_term_id' => $student->program_term_id, 'attended_on' => $data['attended_on']]);
            Audit::record($request->user(), 'event.attended', $record, ['attended_on' => $data['attended_on']], $student->programTerm->program_id);
            return $record;
        });
        return response()->json(['data' => $attendance]);
    }

    public function confirm(Request $request, int $enrollment, int $attendance)
    {
        $actor = $request->user();
        $student = StudentAccess::enrollments($actor)->findOrFail($enrollment);
        abort_unless($actor->hasPermission(Permission::ReviewRequirements) && $actor->isAssignedToProgramTerm($student->program_term_id), 403);
        $record = DB::transaction(function () use ($actor, $student, $attendance) {
            $student = StudentEnrollment::whereKey($student->id)->lockForUpdate()->firstOrFail();
            abort_unless($student->status === EnrollmentStatus::Enrolled, 409, 'The enrollment is closed.');
            $record = EventAttendance::where('student_enrollment_id', $student->id)->lockForUpdate()->findOrFail($attendance);
            if ($record->confirmed_at === null) {
                $record->update(['confirmed_by' => $actor->id, 'confirmed_at' => now()]);
                Audit::record($actor, 'event.confirmed', $record, [], $student->programTerm->program_id);
                $name = $student->programTerm->requirements()->findOrFail($record->program_term_requirement_id)->requirementType->name;
                $student->student->user->notify(new PortalNotification('Event attendance confirmed', $name.': attendance confirmed.'));
            }
            return $record;
        });
        return response()->json(['data' => $record]);
    }
}
