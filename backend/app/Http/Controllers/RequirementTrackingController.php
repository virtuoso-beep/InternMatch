<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\EventAttendance;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Services\StudentAccess;
use App\Support\SyntheticData;
use Illuminate\Http\Request;

/**
 * Coordinator and admin requirement tracking: one page of enrollments with their requirements, submissions
 * and event attendance, loaded in a fixed number of queries. Replaces one request per enrollment.
 *
 * Scope follows StudentAccess (coordinator: own programs; admin: all). Synthetic records are hidden unless
 * "Include synthetic data" is switched on.
 */
class RequirementTrackingController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Coordinator, Role::Admin], true), 403);
        $filters = $request->validate([
            'program_term_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $hideSynthetic = SyntheticData::excluded($request);
        $scope = fn () => StudentAccess::enrollments($user)
            ->when($hideSynthetic, fn ($query) => $query->where('student_enrollments.is_synthetic', false))
            ->when($filters['program_term_id'] ?? null, fn ($query, $id) => $query->where('program_term_id', $id))
            ->when(trim($filters['search'] ?? ''), fn ($query, $search) => $query->whereHas('student', fn ($student) => $student
                ->where('student_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->withTrashed()->where('name', 'like', "%{$search}%"))));
        $page = $scope()
            ->with([
                'student:id,user_id,student_number',
                'student.user' => fn ($query) => $query->withTrashed()->select('id', 'name'),
                'programTerm:id,program_id,academic_term_id',
                'programTerm.program:id,code',
                'programTerm.academicTerm:id,code,name',
                'programTerm.requirements.requirementType:id,name,kind',
                'requirementSubmissions' => fn ($query) => $query->orderByDesc('revision'),
                'requirementSubmissions.document:id,original_name',
                'requirementSubmissions.reviews:id,requirement_submission_id,decision,comments,reviewed_at',
            ])
            ->orderByDesc('enrolled_on')->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        $attendances = EventAttendance::whereIn('student_enrollment_id', $page->getCollection()->modelKeys())->get()->groupBy('student_enrollment_id');
        $page->through(fn (StudentEnrollment $enrollment) => [
            'id' => $enrollment->id,
            'is_synthetic' => (bool) $enrollment->is_synthetic,
            'student' => ['student_number' => $enrollment->student?->student_number, 'user' => ['name' => $enrollment->student?->user?->name ?? 'Archived student account']],
            'program_term' => ['program' => ['code' => $enrollment->programTerm->program->code], 'academic_term' => ['name' => $enrollment->programTerm->academicTerm->name]],
            'requirements' => $enrollment->programTerm->requirements,
            'submissions' => $enrollment->requirementSubmissions,
            'attendances' => $attendances->get($enrollment->id, collect())->values(),
        ]);

        return response()->json([...$page->toArray(), 'summary' => $this->summary($scope), 'synthetic' => SyntheticData::meta($request)], headers: ['Cache-Control' => 'no-store']);
    }

    /**
     * Required document compliance across every enrollment in scope (not only this page), in three queries:
     * latest revision per requirement counts; approved = complete, submitted or under review = pending,
     * rejected = returned, nothing submitted = missing.
     */
    private function summary(\Closure $scope): array
    {
        $enrollments = $scope()->pluck('program_term_id', 'student_enrollments.id');
        $requirements = ProgramTermRequirement::query()
            ->whereIn('program_term_id', $enrollments->unique()->values())
            ->where('is_required', true)
            ->whereHas('requirementType', fn ($type) => $type->where('kind', 'document'))
            ->with('requirementType:id,name')
            ->get(['id', 'program_term_id', 'requirement_type_id'])
            ->groupBy('program_term_id');
        $latest = [];
        RequirementSubmission::query()
            ->whereIn('student_enrollment_id', $scope()->select('student_enrollments.id'))
            ->orderBy('revision')
            ->get(['student_enrollment_id', 'program_term_requirement_id', 'status', 'revision'])
            ->each(function ($submission) use (&$latest) {
                $latest[$submission->student_enrollment_id.':'.$submission->program_term_requirement_id] = $submission->status->value ?? $submission->status;
            });
        $counts = ['complete' => 0, 'pending' => 0, 'returned' => 0, 'missing' => 0];
        $types = [];
        foreach ($enrollments as $enrollmentId => $programTermId) {
            foreach ($requirements->get($programTermId, collect()) as $requirement) {
                $name = $requirement->requirementType->name;
                $types[$name] ??= ['total' => 0, 'approved' => 0];
                $types[$name]['total']++;
                $status = $latest[$enrollmentId.':'.$requirement->id] ?? null;
                $bucket = match ($status) {
                    'approved' => 'complete',
                    'submitted', 'under_review', 'pending' => 'pending',
                    null => 'missing',
                    default => 'returned',
                };
                $counts[$bucket]++;
                if ($bucket === 'complete') {
                    $types[$name]['approved']++;
                }
            }
        }
        ksort($types);

        return [...$counts, 'total' => array_sum($counts), 'enrollments' => $enrollments->count(), 'types' => $types];
    }
}
