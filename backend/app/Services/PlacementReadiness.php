<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\SubmissionStatus;
use App\Models\EventAttendance;
use App\Models\Placement;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Why an enrollment cannot be allocated yet. The batch methods load requirements, submissions, attendance
 * and placements once for any number of enrollments, so lists do not run queries per student.
 */
class PlacementReadiness
{
    public function reasons(StudentEnrollment $enrollment, ?int $exceptPlacementId = null): array
    {
        return $this->reasonsForMany(new Collection([$enrollment]), $exceptPlacementId)[$enrollment->id];
    }

    /** @return array<int, list<string>> reasons keyed by enrollment id */
    public function reasonsForMany(Collection $enrollments, ?int $exceptPlacementId = null): array
    {
        if ($enrollments->isEmpty()) {
            return [];
        }
        $enrollments->loadMissing('programTerm.program');
        $requirements = $this->requirementsFor($enrollments);
        $unmet = $this->unmetFor($enrollments, $requirements);
        $placed = Placement::query()
            ->whereIn('student_enrollment_id', $enrollments->modelKeys())
            ->when($exceptPlacementId, fn ($q) => $q->whereKeyNot($exceptPlacementId))
            ->whereIn('status', ['pending', 'approved', 'active', 'completed'])
            ->distinct()->pluck('student_enrollment_id')->flip();

        $result = [];
        foreach ($enrollments as $enrollment) {
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
            if ($placed->has($enrollment->id)) {
                $reasons[] = 'Enrollment already has a current or completed placement.';
            }
            if ($requirements->get($enrollment->program_term_id, collect())->isEmpty()) {
                $reasons[] = 'No pre-deployment requirements have been configured for this cohort.';
            }
            foreach ($unmet[$enrollment->id] as $name) {
                $reasons[] = $name.' requires approval or confirmed attendance.';
            }
            $result[$enrollment->id] = $reasons;
        }

        return $result;
    }

    /** Names of required pre-deployment requirements that are not yet approved or attended. */
    public function unmetRequirements(StudentEnrollment $enrollment): array
    {
        return $this->unmetRequirementsForMany(new Collection([$enrollment]))[$enrollment->id];
    }

    /** @return array<int, list<string>> unmet requirement names keyed by enrollment id */
    public function unmetRequirementsForMany(Collection $enrollments): array
    {
        return $enrollments->isEmpty() ? [] : $this->unmetFor($enrollments, $this->requirementsFor($enrollments));
    }

    /** Required pre-deployment requirements grouped by program term, in id order. */
    private function requirementsFor(Collection $enrollments): \Illuminate\Support\Collection
    {
        return ProgramTermRequirement::query()
            ->whereIn('program_term_id', $enrollments->pluck('program_term_id')->unique()->values())
            ->where('is_required', true)->where('required_before_deployment', true)
            ->with('requirementType')->orderBy('id')->get()
            ->groupBy('program_term_id');
    }

    private function unmetFor(Collection $enrollments, \Illuminate\Support\Collection $requirements): array
    {
        $ids = $enrollments->modelKeys();
        $attended = EventAttendance::query()->whereIn('student_enrollment_id', $ids)->whereNotNull('confirmed_at')
            ->get(['student_enrollment_id', 'program_term_requirement_id'])
            ->mapWithKeys(fn ($a) => [$a->student_enrollment_id.':'.$a->program_term_requirement_id => true]);
        // Latest revision wins: ordered ascending, later rows overwrite earlier ones.
        $latest = [];
        RequirementSubmission::query()->whereIn('student_enrollment_id', $ids)->orderBy('revision')->orderBy('id')
            ->get(['id', 'student_enrollment_id', 'program_term_requirement_id', 'revision', 'status'])
            ->each(function ($submission) use (&$latest) {
                $latest[$submission->student_enrollment_id.':'.$submission->program_term_requirement_id] = $submission->status;
            });

        $unmet = [];
        foreach ($enrollments as $enrollment) {
            $unmet[$enrollment->id] = [];
            foreach ($requirements->get($enrollment->program_term_id, collect()) as $requirement) {
                $key = $enrollment->id.':'.$requirement->id;
                $complete = $requirement->requirementType->kind === 'event'
                    ? isset($attended[$key])
                    : ($latest[$key] ?? null) === SubmissionStatus::Approved;
                if (! $complete) {
                    $unmet[$enrollment->id][] = $requirement->requirementType->name;
                }
            }
        }

        return $unmet;
    }
}
