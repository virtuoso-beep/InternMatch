<?php

namespace App\Services;

use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequirementReviewService
{
    public static function review(User $actor, RequirementSubmission $submission, array $input): RequirementSubmission
    {
        Gate::forUser($actor)->authorize('review', $submission);
        $data = Validator::make($input, [
            'status' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'comments' => ['required_if:status,rejected', 'nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $submission, $data) {
            StudentEnrollment::whereKey($submission->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $record = RequirementSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('review', $record);
            $latest = RequirementSubmission::where('student_enrollment_id', $record->student_enrollment_id)
                ->where('program_term_requirement_id', $record->program_term_requirement_id)->max('revision');
            abort_unless($record->revision === (int) $latest, 409, 'Review the latest submission revision.');
            $record->update(['status' => $data['status']]);
            if ($data['status'] !== 'under_review') {
                $record->reviews()->create(['reviewed_by' => $actor->id, 'decision' => $data['status'], 'comments' => $data['comments'] ?? null, 'reviewed_at' => now()]);
            }
            Audit::record($actor, 'requirement.reviewed', $record, ['status' => $data['status']], $record->programTerm->program_id);
            $record->studentEnrollment->student->user->notify(new PortalNotification('Requirement status updated', $record->programTermRequirement->requirementType->name.': '.str_replace('_', ' ', $data['status']).'.'));

            return $record->load('reviews');
        });
    }
}
