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
use App\Services\OpportunityEligibility;
use App\Services\PlacementAccess;
use App\Services\PlacementReadiness;
use App\Services\StudentAccess;
use App\Support\SyntheticData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate(['program_id' => ['nullable', 'integer', 'exists:programs,id'], 'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'], 'year_level' => ['nullable', 'integer', 'between:1,10']]);
        $programs = Program::query()->where('is_active', true);
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
        $hideSynthetic = SyntheticData::excluded($request);
        $enrollments->when($hideSynthetic, fn ($query) => $query->where('student_enrollments.is_synthetic', false))
            ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('programTerm', fn ($query) => $query->where('program_id', $id)))
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
            $real = fn ($query) => $query->when($hideSynthetic, fn ($q) => $q->where('is_synthetic', false));
            $stats[] = ['label' => 'Active accounts (system-wide)', 'value' => $real(User::where('status', 'active'))->count()];
            $stats[] = ['label' => 'Active hosts (system-wide)', 'value' => $real(HostEstablishment::where('is_active', true))->count()];
            $stats[] = ['label' => 'Published opportunities (system-wide)', 'value' => $real(Opportunity::where('status', 'published'))->count()];
        } elseif (in_array($user->role, [Role::Coordinator, Role::Supervisor], true)) {
            $stats[] = ['label' => 'Accessible active hosts (all assigned programs)', 'value' => HostAccess::query($user)->where('is_active', true)
                ->when($hideSynthetic, fn ($q) => $q->where('host_establishments.is_synthetic', false))->count()];
        }
        if ($user->role === Role::Coordinator) {
            array_push($stats, ...$this->coordinatorTiles($enrollments, $hideSynthetic));
        }
        $breakdown = (clone $enrollments)->join('program_terms', 'program_terms.id', '=', 'student_enrollments.program_term_id')
            ->join('programs', 'programs.id', '=', 'program_terms.program_id')->selectRaw('programs.code, COUNT(*) as enrollments')->groupBy('programs.id', 'programs.code')->orderBy('programs.code')->get();

        return response()->json(['stats' => $stats, 'programs' => $availablePrograms, 'program_breakdown' => $breakdown, 'synthetic' => SyntheticData::meta($request), 'generated_at' => now()->toIso8601String()], headers: ['Cache-Control' => 'no-store']);
    }

    /** Spec §11 coordinator tiles, built on the existing readiness, eligibility and monitoring-flag rules. */
    private function coordinatorTiles(Builder $enrollments, bool $hideSynthetic = false): array
    {
        $enrolled = (clone $enrollments)->where('status', 'enrolled')->with('programTerm.program')->get();
        $awaiting = (clone $enrollments)->where('status', 'enrolled')
            ->whereDoesntHave('placements', fn ($query) => $query->whereIn('status', ['pending', 'approved', 'active', 'completed']))
            ->with('programTerm.program')->get();
        $readiness = app(PlacementReadiness::class);
        $incomplete = $awaiting->filter(fn ($enrollment) => $readiness->unmetRequirements($enrollment) !== [])->count();
        // Slots per host are shared by its opportunities, so count min(host remaining, sum of opportunity remaining).
        $eligibility = app(OpportunityEligibility::class);
        $slots = 0;
        foreach ($enrolled->unique('program_term_id') as $enrollment) {
            $perHost = [];
            Opportunity::where('academic_term_id', $enrollment->programTerm->academic_term_id)->where('status', 'published')
                ->when($hideSynthetic, fn ($q) => $q->where('is_synthetic', false))->get()
                ->each(function ($opportunity) use ($eligibility, $enrollment, &$perHost) {
                    if ($info = $eligibility->inspect($enrollment, $opportunity)) {
                        $host = $opportunity->host_establishment_id;
                        $perHost[$host]['host'] = $info['host_capacity_remaining'];
                        $perHost[$host]['opportunities'] = ($perHost[$host]['opportunities'] ?? 0) + $info['opportunity_capacity_remaining'];
                    }
                });
            $slots += array_sum(array_map(fn ($row) => max(0, min($row['host'], $row['opportunities'])), $perHost));
        }
        $attention = DB::table('monitoring_flags')->join('placements', 'placements.id', '=', 'monitoring_flags.placement_id')
            ->whereNull('monitoring_flags.resolved_at')->whereIn('placements.student_enrollment_id', (clone $enrollments)->select('id'))
            ->distinct()->count('placements.student_enrollment_id');

        return [
            ['label' => 'Students awaiting placement', 'value' => $awaiting->count()],
            ['label' => 'Awaiting students with incomplete requirements', 'value' => $incomplete],
            ['label' => 'Available placement slots', 'value' => $slots],
            ['label' => 'Students needing attention (open monitoring flags)', 'value' => $attention],
        ];
    }
}
