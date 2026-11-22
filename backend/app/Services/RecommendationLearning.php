<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Recommendation learning (spec §9, Development Guide Phase 9).
 *
 * Labels are approved coordinator judgments on real (non-test) students: suitable and not suitable train and
 * evaluate; uncertain is counted and reported but excluded. Only the latest approved judgment per
 * recommendation counts. Training happens only with at least 30 suitable and 30 not-suitable labels; the AI
 * service then evaluates with leave-one-student-out and installs the model only if it is better than
 * similarity alone and every program has both labels. Every run is recorded in ranker_runs.
 *
 * Test data (synthetic coordinator labels) can be used only when local test mode allows it; it never counts
 * toward the 30/30 rule for real judgments.
 */
class RecommendationLearning
{
    public const MIN_PER_CLASS = 30;

    /** Test runs apply the same 30/30 rule, so each program contributes up to 30 suitable and 30 not suitable. */
    public const TEST_DATA_PER_PROGRAM = 60;

    /** Latest approved judgment per recommendation on real students, with what training needs. */
    public function realJudgments(?array $programIds = null): Collection
    {
        $latest = DB::table('placement_judgments')->whereNotNull('approved_at')
            ->selectRaw('MAX(id) as id')->groupBy('recommendation_id');

        return DB::table('placement_judgments as j')
            ->joinSub($latest, 'latest', 'latest.id', '=', 'j.id')
            ->join('recommendations as r', 'r.id', '=', 'j.recommendation_id')
            ->join('student_enrollments as e', 'e.id', '=', 'r.student_enrollment_id')
            ->join('program_terms as pt', 'pt.id', '=', 'e.program_term_id')
            ->join('programs as p', 'p.id', '=', 'pt.program_id')
            ->where('e.is_synthetic', false)
            ->when($programIds !== null, fn ($q) => $q->whereIn('pt.program_id', $programIds))
            ->orderBy('j.id')
            ->get(['j.judgment', 'r.similarity_score', 'r.distance_km', 'r.snapshot', 'e.student_id', 'p.id as program_id', 'p.code as program']);
    }

    /** Live counts per program and overall (suitable, not suitable, uncertain). */
    public function counts(?array $programIds = null): array
    {
        $judgments = $this->realJudgments($programIds);
        $count = fn (Collection $rows) => [
            'suitable' => $rows->where('judgment', 'suitable')->count(),
            'not_suitable' => $rows->where('judgment', 'unsuitable')->count(),
            'uncertain' => $rows->where('judgment', 'uncertain')->count(),
        ];
        $byProgram = $judgments->groupBy('program')->map($count);
        if ($programIds !== null) { // every assigned program is listed, with zeros when it has no judgments yet
            foreach (DB::table('programs')->whereIn('id', $programIds)->pluck('code') as $code) {
                $byProgram[$code] ??= ['suitable' => 0, 'not_suitable' => 0, 'uncertain' => 0];
            }
        }
        $byProgram = $byProgram->sortKeys();

        return ['overall' => $count($judgments), 'by_program' => $byProgram->all(), 'required' => self::MIN_PER_CLASS];
    }

    /** Training rows from real judgments (suitable / not suitable only). */
    public function realRows(): array
    {
        return $this->realJudgments()->whereIn('judgment', ['suitable', 'unsuitable'])->map(function ($j) {
            $snapshot = json_decode((string) $j->snapshot, true) ?: [];

            return ['student' => 'S'.$j->student_id, 'program' => $j->program, 'similarity' => (float) $j->similarity_score,
                'distance_km' => $j->distance_km === null ? null : (float) $j->distance_km,
                'keyword_overlap' => $this->keywordOverlap($snapshot['competencies'] ?? []),
                'label' => $j->judgment === 'suitable' ? 1 : 0, 'synthetic' => false];
        })->values()->all();
    }

    /** Share of the opportunity's required competencies that the student lists (spec §9 baseline). */
    private function keywordOverlap(array $competencies): ?float
    {
        if ($competencies === []) {
            return null;
        }

        return round(collect($competencies)->filter(fn ($c) => ($c['level'] ?? null) !== null)->count() / count($competencies), 4);
    }

    /** Test data: synthetic coordinator labels, up to $perProgram per program, half suitable and half not. */
    public function testRows(int $perProgram = self::TEST_DATA_PER_PROGRAM): array
    {
        $labels = DB::table('coordinator_labels as l')->join('programs as p', 'p.id', '=', 'l.program_id')
            ->where('l.is_synthetic', true)->whereNotNull('l.suitable')->orderBy('l.id')
            ->get(['l.id', 'l.student_id', 'l.skill_match_score', 'l.distance_km', 'l.suitable', 'p.code as program']);

        return self::balancedPerProgram($labels, $perProgram, fn ($l) => (bool) $l->suitable, 'program')
            ->map(fn ($l) => ['student' => 'T'.$l->student_id, 'program' => $l->program, 'similarity' => (float) $l->skill_match_score,
                'distance_km' => $l->distance_km === null ? null : (float) $l->distance_km, 'keyword_overlap' => null,
                'label' => $l->suitable ? 1 : 0, 'synthetic' => true])->values()->all();
    }

    /** At most $perProgram per program, split evenly by class (lowest IDs first), topped up from the other class. */
    public static function balancedPerProgram(Collection $rows, int $perProgram, \Closure $isSuitable, string $programKey): Collection
    {
        return $rows->groupBy($programKey)->flatMap(function (Collection $group) use ($perProgram, $isSuitable) {
            [$suitable, $unsuitable] = $group->partition($isSuitable);
            $take = min($suitable->count(), max(intdiv($perProgram, 2), $perProgram - $unsuitable->count()));

            return $suitable->take($take)->concat($unsuitable->take(min($unsuitable->count(), $perProgram - $take)));
        })->sortBy('id')->values();
    }

    public static function testModeAllowed(): bool
    {
        return (bool) config('matching.allow_test_model');
    }

    /**
     * Check the rule, train and evaluate in the AI service, install only if it passes, and record the run.
     * Returns the ranker_runs row.
     */
    public function run(?User $actor, string $source, bool $useTestData = false): object
    {
        abort_if($useTestData && ! self::testModeAllowed(), 422, 'Test data can be used only in local test mode.');
        $lock = Cache::lock('recommendation-learning', 300);
        abort_unless($lock->get(), 409, 'An update is already running. Try again in a few minutes.');
        try {
            $started = now();
            $real = $this->counts()['overall'];
            $rows = $useTestData ? $this->testRows() : $this->realRows();
            $used = ['suitable' => collect($rows)->where('label', 1)->count(), 'not_suitable' => collect($rows)->where('label', 0)->count()];
            $base = ['user_id' => $actor?->id, 'source' => $source, 'used_test_data' => $useTestData,
                'suitable' => $used['suitable'], 'not_suitable' => $used['not_suitable'],
                'uncertain' => $useTestData ? 0 : $real['uncertain'],
                'students' => collect($rows)->pluck('student')->unique()->count(), 'started_at' => $started];

            if ($used['suitable'] < self::MIN_PER_CLASS || $used['not_suitable'] < self::MIN_PER_CLASS) {
                return $this->record($actor, $base + ['result' => 'not_enough_data',
                    'reason' => 'Needs at least '.self::MIN_PER_CLASS.' suitable and '.self::MIN_PER_CLASS.' not-suitable judgments.',
                    'fallback' => $this->fallbackEvaluation($rows)]);
            }
            try {
                $report = Http::connectTimeout(3)->timeout(120)->post(rtrim(config('matching.url'), '/').'/ml/train', [
                    'rows' => $rows, 'min_per_class' => self::MIN_PER_CLASS,
                ])->throw()->json();
            } catch (ConnectionException|RequestException $error) {
                return $this->record($actor, $base + ['result' => 'failed', 'reason' => 'The recommendation service could not complete the update: '.$error->getMessage()]);
            }

            return $this->record($actor, $base + [
                'result' => in_array($report['result'] ?? null, ['updated', 'not_enough_data', 'not_reliable'], true) ? $report['result'] : 'failed',
                'reason' => $report['reason'] ?? null, 'metrics' => $report['metrics'] ?? null, 'fallback' => $report['fallback'] ?? null,
                'model_trained_at' => isset($report['model']['trained_at']) ? Carbon::parse($report['model']['trained_at']) : null,
                'model_sha256' => $report['model']['model_sha256'] ?? null,
            ]);
        } finally {
            $lock->release();
        }
    }

    private function record(?User $actor, array $row): object
    {
        $row = $row + ['metrics' => null, 'fallback' => null, 'reason' => null];
        $row['metrics'] = $row['metrics'] === null ? null : json_encode($row['metrics']);
        $row['fallback'] = $row['fallback'] === null ? null : json_encode($row['fallback']);
        $id = DB::table('ranker_runs')->insertGetId($row + ['finished_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        if ($actor) {
            Audit::record($actor, 'recommendations.learning_run', $actor, ['run_id' => $id, 'result' => $row['result'], 'used_test_data' => $row['used_test_data']]);
        }

        return DB::table('ranker_runs')->find($id);
    }

    /**
     * Fallback evaluation (spec §9): Precision@3, Recall@3 and NDCG@3 per student with both a suitable and a
     * not-suitable label, averaged; current ranking (similarity, then distance) versus keyword overlap and
     * distance only. Null when no student qualifies yet.
     */
    public function fallbackEvaluation(array $rows): ?array
    {
        $eligible = collect($rows)->groupBy('student')->filter(fn ($g) => $g->contains('label', 1) && $g->contains('label', 0));
        if ($eligible->isEmpty()) {
            return null;
        }
        $far = PHP_FLOAT_MAX;
        $orders = [
            'similarity' => fn ($g) => $g->sort(fn ($a, $b) => [$b['similarity'], $a['distance_km'] ?? $far] <=> [$a['similarity'], $b['distance_km'] ?? $far]),
            'keyword_overlap' => fn ($g) => $g->sortByDesc(fn ($r) => $r['keyword_overlap'] ?? 0.0),
            'distance_only' => fn ($g) => $g->sortBy(fn ($r) => $r['distance_km'] ?? $far),
        ];
        $result = ['students' => $eligible->count()];
        foreach ($orders as $name => $order) {
            $values = $eligible->map(fn ($g) => $this->rankingMetrics($order($g)->pluck('label')->values()->all()));
            $result[$name] = ['precision_at_3' => $values->avg(0), 'recall_at_3' => $values->avg(1), 'ndcg_at_3' => $values->avg(2)];
        }

        return $result;
    }

    private function rankingMetrics(array $labels, int $k = 3): array
    {
        $top = array_slice($labels, 0, $k);
        $relevant = array_sum($labels);
        $dcg = $idcg = 0.0;
        foreach ($top as $i => $rel) {
            $dcg += $rel / log($i + 2, 2);
        }
        $ideal = $labels;
        rsort($ideal);
        foreach (array_slice($ideal, 0, $k) as $i => $rel) {
            $idcg += $rel / log($i + 2, 2);
        }

        return [array_sum($top) / min($k, count($labels)), $relevant ? array_sum($top) / $relevant : 0.0, $idcg ? $dcg / $idcg : 0.0];
    }

    /** What the AI service reports about the installed ranker. */
    public function status(): array
    {
        if (config('matching.reranker') !== 'auto') {
            return ['active' => false, 'test_model' => false, 'reason' => 'Supervised reranking is switched off (MATCHING_RERANKER=off).', 'card' => null];
        }
        try {
            $status = Http::connectTimeout(2)->timeout(4)->get(rtrim(config('matching.url'), '/').'/recommendations/ml/status')->throw()->json();
        } catch (ConnectionException|RequestException) {
            return ['active' => false, 'test_model' => false, 'reason' => 'The recommendation service did not report its status.', 'card' => null];
        }

        return ['active' => (bool) ($status['active'] ?? false), 'test_model' => (bool) ($status['card']['synthetic'] ?? false),
            'reason' => $status['reason'] ?? null, 'card' => $status['card'] ?? null];
    }
}
