<?php

namespace App\Http\Controllers;

use App\Enums\PlacementStatus;
use App\Enums\Role;
use App\Models\Evaluation;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Recommendation;
use App\Services\EvaluationScoring;
use App\Services\Haversine;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramAnalyticsController extends Controller
{
    public function __invoke(Request $request, EvaluationScoring $scoring)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Dean, Role::Coordinator], true), 403);
        $programs = $user->programs()->orderBy('code')->get();
        $programIds = $programs->pluck('id');
        $enrollments = StudentAccess::enrollments($user)->with('programTerm.academicTerm')->get();
        $placements = Placement::whereIn('student_enrollment_id', $enrollments->pluck('id'))
            ->whereIn('status', ['approved', 'active', 'completed'])->with(['studentEnrollment.student.user.profile', 'hostEstablishment'])->get();
        $evaluations = Evaluation::whereIn('placement_id', $placements->pluck('id'))->where('status', 'submitted')
            ->with(['scores', 'evaluationRubric.criteria'])->get();
        $summary = function ($group) use ($placements) {
            $records = $placements->whereIn('student_enrollment_id', $group->pluck('id'));
            $placed = $records->pluck('student_enrollment_id')->unique()->count();
            $completed = $records->where('status', PlacementStatus::Completed)->pluck('student_enrollment_id')->unique()->count();

            return ['enrollments' => $group->count(), 'placed' => $placed, 'completed' => $completed,
                'placement_rate' => $group->isEmpty() ? null : round(100 * $placed / $group->count(), 2),
                'completion_rate' => $group->isEmpty() ? null : round(100 * $completed / $group->count(), 2)];
        };
        $performance = $programs->map(function ($program) use ($enrollments, $placements, $evaluations, $scoring, $summary) {
            $group = $enrollments->filter(fn ($e) => $e->programTerm->program_id === $program->id);
            $records = $placements->whereIn('student_enrollment_id', $group->pluck('id'));
            $scores = $evaluations->whereIn('placement_id', $records->pluck('id'))->map(fn ($e) => $scoring->percentage($e))->filter(fn ($v) => $v !== null);

            return ['code' => $program->code, 'name' => $program->name, ...$summary($group),
                'evaluation_percent' => $scores->isEmpty() ? null : round($scores->avg(), 2), 'evaluation_count' => $scores->count()];
        });
        $hosts = HostEstablishment::where(function ($q) use ($programIds) {
            $q->whereIn('id', DB::table('host_program_capacity')->whereIn('program_id', $programIds)->select('host_establishment_id'))
                ->orWhereHas('opportunities.programs', fn ($p) => $p->whereIn('programs.id', $programIds))
                ->orWhereHas('moas.programs', fn ($p) => $p->whereIn('programs.id', $programIds));
        })->with(['moas' => fn ($q) => $q->where(fn ($m) => $m->where('is_institution_wide', true)->orWhereHas('programs', fn ($p) => $p->whereIn('programs.id', $programIds)))])->orderBy('name')->get();
        $today = today();
        $inventory = $hosts->map(fn ($host) => [
            'id' => $host->id, 'name' => $host->name, 'industry' => $host->industry, 'city' => $host->city, 'is_active' => $host->is_active,
            'current_agreements' => $host->moas->filter(fn ($m) => $m->status->value === 'active' && $m->effective_on && $m->effective_on->lte($today) && $m->expires_on && $m->expires_on->gte($today))->count(),
            'agreements_expiring_90_days' => $host->moas->filter(fn ($m) => $m->status->value === 'active' && $m->effective_on && $m->effective_on->lte($today) && $m->expires_on && $m->expires_on->betweenIncluded($today, $today->copy()->addDays(90)))->count(),
        ]);
        $distances = collect();
        $missing = 0;
        foreach ($placements->whereIn('status', [PlacementStatus::Approved, PlacementStatus::Active]) as $placement) {
            $profile = $placement->studentEnrollment->student->user->profile;
            $host = $placement->hostEstablishment;
            if ($profile?->latitude === null || $profile?->longitude === null || $host->latitude === null || $host->longitude === null) {
                $missing++;

                continue;
            }
            $distances->push(Haversine::kilometers((float) $profile->latitude, (float) $profile->longitude, (float) $host->latitude, (float) $host->longitude));
        }
        $distanceBands = ['Under 5 km' => 0, '5 to under 10 km' => 0, '10 to 20 km' => 0, 'Over 20 km' => 0];
        foreach ($distances as $distance) {
            $distanceBands[$distance < 5 ? 'Under 5 km' : ($distance < 10 ? '5 to under 10 km' : ($distance <= 20 ? '10 to 20 km' : 'Over 20 km'))]++;
        }
        $similarityBands = ['90 to 100%' => 0, '80 to under 90%' => 0, '70 to under 80%' => 0, 'Below 70%' => 0];
        foreach (Recommendation::whereIn('student_enrollment_id', $enrollments->pluck('id'))->pluck('similarity_score') as $score) {
            $similarityBands[$score >= .9 ? '90 to 100%' : ($score >= .8 ? '80 to under 90%' : ($score >= .7 ? '70 to under 80%' : 'Below 70%'))]++;
        }
        $demand = Opportunity::where('status', 'published')->whereHas('programs', fn ($q) => $q->whereIn('programs.id', $programIds))->with('competencies')->get()
            ->flatMap(fn ($o) => $o->competencies->pluck('name'))->countBy()->sortDesc();
        $bands = fn ($values) => collect($values)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values();

        return response()->json([
            'programs' => $performance->values(), 'hosts' => $inventory->values(),
            'equity' => ['median_km' => $distances->isEmpty() ? null : round($distances->median(), 2), 'measured_placements' => $distances->count(), 'missing_coordinates' => $missing, 'over_20_km' => $distances->filter(fn ($d) => $d > 20)->count(), 'bands' => $bands($distanceBands)],
            'similarity_bands' => $bands($similarityBands), 'competency_demand' => $bands($demand),
            'terms' => $enrollments->groupBy('programTerm.academic_term_id')->map(fn ($group) => ['term' => $group->first()->programTerm->academicTerm->name, ...$summary($group)])->values(),
            'generated_at' => now()->toIso8601String(),
        ], headers: ['Cache-Control' => 'no-store']);
    }
}
