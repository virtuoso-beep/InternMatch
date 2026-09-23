<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Evaluation;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\HostAccess;
use App\Services\PlacementAccess;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate(['program_id' => ['nullable', 'integer', 'exists:programs,id'], 'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'], 'year_level' => ['nullable', 'integer', 'between:1,10']]);
        $programs = Program::query();
        if (in_array($user->role, [Role::Coordinator, Role::Dean], true)) {
            $programs->whereIn('id', $user->programs()->select('programs.id'));
        } elseif ($user->role === Role::Student) {
            $programs->whereHas('programTerms.enrollments.student', fn ($query) => $query->where('user_id', $user->id));
        } elseif ($user->role === Role::Supervisor) {
            $programs->whereHas('programTerms.enrollments.placements', fn ($query) => $query->whereIn('id', PlacementAccess::query($user)->select('id')));
        }
        $availablePrograms = $programs->orderBy('code')->get(['id', 'code', 'name']);
        if (isset($filters['program_id'])) {
            abort_unless($availablePrograms->contains('id', (int) $filters['program_id']), 403);
        }
        $enrollments = StudentAccess::enrollments($user);
        if ($user->role === Role::Supervisor) {
            $enrollments = StudentEnrollment::whereHas('placements', fn ($query) => $query->whereIn('id', PlacementAccess::query($user)->select('id')));
        }
        $enrollments->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('programTerm', fn ($query) => $query->where('program_id', $id)))
            ->when($filters['academic_term_id'] ?? null, fn ($query, $id) => $query->whereHas('programTerm', fn ($query) => $query->where('academic_term_id', $id)))
            ->when($filters['year_level'] ?? null, fn ($query, $year) => $query->where('year_level', $year));
        $placements = ($user->role === Role::Dean ? Placement::query() : PlacementAccess::query($user))
            ->whereIn('student_enrollment_id', (clone $enrollments)->select('id'));
        $timeLogs = TimeLog::whereIn('placement_id', (clone $placements)->select('id'));
        $submissions = RequirementSubmission::whereIn('student_enrollment_id', (clone $enrollments)->select('id'))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('requirement_submissions as newer')
                ->whereColumn('newer.student_enrollment_id', 'requirement_submissions.student_enrollment_id')
                ->whereColumn('newer.program_term_requirement_id', 'requirement_submissions.program_term_requirement_id')
                ->whereColumn('newer.revision', '>', 'requirement_submissions.revision'));
        $stats = [
            ['label' => 'Enrollment records', 'value' => (clone $enrollments)->count()],
            ['label' => 'Active placements', 'value' => (clone $placements)->where('status', 'active')->count()],
            ['label' => 'Pending placements', 'value' => (clone $placements)->where('status', 'pending')->count()],
            ['label' => 'Completed placements', 'value' => (clone $placements)->where('status', 'completed')->count()],
            ['label' => 'Certified hours', 'value' => round((clone $timeLogs)->where('status', 'verified')->sum('credited_minutes') / 60, 2)],
            ['label' => 'Time logs awaiting verification', 'value' => (clone $timeLogs)->where('status', 'pending')->count()],
            ['label' => 'Submissions awaiting review', 'value' => (clone $submissions)->whereIn('status', ['submitted', 'under_review'])->count()],
            ['label' => 'Submitted evaluations', 'value' => Evaluation::whereIn('placement_id', (clone $placements)->select('id'))->where('status', 'submitted')->count()],
            ['label' => 'Stored recommendation generations', 'value' => DB::table('recommendations')->whereIn('student_enrollment_id', (clone $enrollments)->select('id'))->distinct()->count('generation_id')],
        ];
        if ($user->role === Role::Admin) {
            $stats[] = ['label' => 'Active accounts (system-wide)', 'value' => User::where('status', 'active')->count()];
            $stats[] = ['label' => 'Active hosts (system-wide)', 'value' => HostEstablishment::where('is_active', true)->count()];
            $stats[] = ['label' => 'Published opportunities (system-wide)', 'value' => Opportunity::where('status', 'published')->count()];
        } elseif (in_array($user->role, [Role::Coordinator, Role::Supervisor], true)) {
            $stats[] = ['label' => 'Accessible active hosts (all assigned programs)', 'value' => HostAccess::query($user)->where('is_active', true)->count()];
        }
        $breakdown = (clone $enrollments)->join('program_terms', 'program_terms.id', '=', 'student_enrollments.program_term_id')
            ->join('programs', 'programs.id', '=', 'program_terms.program_id')->selectRaw('programs.code, COUNT(*) as enrollments')->groupBy('programs.id', 'programs.code')->orderBy('programs.code')->get();

        return response()->json(['stats' => $stats, 'programs' => $availablePrograms, 'program_breakdown' => $breakdown, 'generated_at' => now()->toIso8601String()], headers: ['Cache-Control' => 'no-store']);
    }
}
