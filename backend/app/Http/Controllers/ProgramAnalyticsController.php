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
use App\Support\SyntheticData;
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
        $hideSynthetic = SyntheticData::excluded($request);
        $enrollments = StudentAccess::enrollments($user)->when($hideSynthetic, fn ($q) => $q->where('student_enrollments.is_synthetic', false))
            ->with('programTerm.academicTerm')->get();
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
        $hosts = HostEstablishment::when($hideSynthetic, fn ($q) => $q->where('is_synthetic', false))->where(function ($q) use ($programIds) {
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
            $profile = $placement->studentEnrollment->student->user?->profile;
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
        // Raw cosine similarity scores (spec: never shown as a percentage).
        $similarityBands = ['0.90 to 1.00' => 0, '0.80 to under 0.90' => 0, '0.70 to under 0.80' => 0, 'Below 0.70' => 0];
        foreach (Recommendation::whereIn('student_enrollment_id', $enrollments->pluck('id'))->pluck('similarity_score') as $score) {
            $similarityBands[$score >= .9 ? '0.90 to 1.00' : ($score >= .8 ? '0.80 to under 0.90' : ($score >= .7 ? '0.70 to under 0.80' : 'Below 0.70'))]++;
        }
        $demand = Opportunity::where('status', 'published')->when($hideSynthetic, fn ($q) => $q->where('is_synthetic', false))->whereHas('programs', fn ($q) => $q->whereIn('programs.id', $programIds))->with('competencies')->get()
            ->flatMap(fn ($o) => $o->competencies->pluck('name'))->countBy()->sortDesc();
        $bands = fn ($values) => collect($values)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values();

        return response()->json([
            'programs' => $performance->values(), 'hosts' => $inventory->values(),
            'equity' => ['median_km' => $distances->isEmpty() ? null : round($distances->median(), 2), 'measured_placements' => $distances->count(), 'missing_coordinates' => $missing, 'over_20_km' => $distances->filter(fn ($d) => $d > 20)->count(), 'bands' => $bands($distanceBands)],
            'similarity_bands' => $bands($similarityBands), 'competency_demand' => $bands($demand),
            'weekly' => $this->weekly($placements->pluck('id')->all()),
            'terms' => $enrollments->groupBy('programTerm.academic_term_id')->map(fn ($group) => ['term' => $group->first()->programTerm->academicTerm->name, ...$summary($group)])->values(),
            'synthetic' => SyntheticData::meta($request), 'generated_at' => now()->toIso8601String(),
        ], headers: ['Cache-Control' => 'no-store']);
    }

    /**
     * The last 16 weeks (Monday start, Manila dates as stored), one aggregate query: certified hours from
     * verified time logs, time logs submitted, and interns who logged time. Weeks without logs are zero.
     *
     * @param  array<int>  $placementIds
     * @return array<int, array{week: string, label: string, certified_hours: float, logs: int, interns: int}>
     */
    private function weekly(array $placementIds): array
    {
        $first = today()->startOfWeek()->subWeeks(15);
        $rows = $placementIds === [] ? collect() : DB::table('time_logs')
            ->whereIn('placement_id', $placementIds)
            ->where('work_date', '>=', $first->toDateString())
            ->selectRaw("DATE_FORMAT(DATE_SUB(work_date, INTERVAL WEEKDAY(work_date) DAY), '%Y-%m-%d') as week")
            ->selectRaw("SUM(CASE WHEN status = 'verified' THEN COALESCE(credited_minutes, 0) ELSE 0 END) as minutes")
            ->selectRaw('COUNT(*) as logs, COUNT(DISTINCT placement_id) as interns')
            ->groupBy('week')->get()->keyBy('week');
        $weeks = [];
        for ($i = 0; $i < 16; $i++) {
            $start = $first->copy()->addWeeks($i);
            $row = $rows->get($start->toDateString());
            $weeks[] = [
                'week' => $start->toDateString(), 'label' => $start->format('M j'),
                'certified_hours' => round(((int) ($row->minutes ?? 0)) / 60, 1),
                'logs' => (int) ($row->logs ?? 0), 'interns' => (int) ($row->interns ?? 0),
            ];
        }

        return $weeks;
    }
}
