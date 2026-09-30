<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\SubmissionStatus;
use App\Models\EventAttendance;
use App\Models\StudentEnrollment;

class PlacementReadiness
{
    public function reasons(StudentEnrollment $enrollment): array
    {
        $reasons = [];
        $term = $enrollment->programTerm;
        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            $reasons[] = 'Enrollment is not active.';
        }
        if (! $term->program->required_ojt_hours) {
            $reasons[] = 'Program OJT hours have not been approved.';
        }
        if ($term->program->code === 'BSIT' && $enrollment->year_level !== 4) {
            $reasons[] = 'BSIT placement requires fourth-year enrollment.';
        }
        if (! $enrollment->academic_eligibility_confirmed || ! $enrollment->eligibility_confirmed_at) {
            $reasons[] = 'Coordinator academic eligibility confirmation is missing or negative.';
        }
        if ($enrollment->placements()->whereIn('status', ['pending', 'approved', 'active', 'completed'])->exists()) {
            $reasons[] = 'Enrollment already has a current or completed placement.';
        }
        $requirements = $term->requirements()->where('is_required', true)->where('required_before_deployment', true)->with('requirementType')->get();
        if ($requirements->isEmpty()) {
            $reasons[] = 'No pre-deployment requirements have been configured for this cohort.';
        }
        foreach ($requirements as $requirement) {
            $complete = $requirement->requirementType->kind === 'event'
                ? EventAttendance::where('student_enrollment_id', $enrollment->id)->where('program_term_requirement_id', $requirement->id)->whereNotNull('confirmed_at')->exists()
                : $enrollment->requirementSubmissions()->where('program_term_requirement_id', $requirement->id)->latest('revision')->first()?->status === SubmissionStatus::Approved;
            if (! $complete) {
                $reasons[] = $requirement->requirementType->name.' requires approval or confirmed attendance.';
            }
        }

        return $reasons;
    }
}
