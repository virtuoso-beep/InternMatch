<?php

namespace App\Services;

use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Models\Recommendation;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateRecommendations
{
    public function __construct(private SemanticEmbeddings $embeddings, private OpportunityEligibility $eligibility) {}

    public function generate(User $actor, StudentEnrollment $enrollment): array
    {
        $studentVector = $this->embeddings->cached($enrollment->student);
        abort_unless($studentVector, 409, 'Competency embeddings are not ready. Save your competencies and retry after processing.');
        $candidates = Opportunity::where('academic_term_id', $enrollment->programTerm->academic_term_id)
            ->where('status', 'published')->whereHas('programs', fn ($q) => $q->whereKey($enrollment->programTerm->program_id))->get();
        $vectors = [];
        $sources = [];
        $profile = $enrollment->student->user?->profile;
        foreach ($candidates as $opportunity) {
            $facts = $this->eligibility->inspect($enrollment, $opportunity);
            if (! $facts) {
                continue;
            }
            $cached = $this->embeddings->cached($opportunity);
            abort_unless($cached, 409, 'Eligible opportunity embeddings are still processing. Please retry shortly.');
            // Only eligible opportunities reach ranking (spec §6). The ranker's features are similarity, distance
            // and program (spec §9); agreement and capacity are hard constraints, not ranking signals.
            $vectors[] = ['id' => $opportunity->id, 'vector' => json_decode($cached->vector, true, flags: JSON_THROW_ON_ERROR),
                'distance_km' => $this->distance($profile, $opportunity->hostEstablishment)];
            $sources[$opportunity->id] = $cached;
        }
        abort_if(count($vectors) > 1000, 422, 'This cohort exceeds the configured matching batch limit.');
        [$scores, $mlScores, $method, $ranker] = $vectors === []
            ? [[], [], 'cosine_then_distance', null]
            : $this->rank(json_decode($studentVector->vector, true, flags: JSON_THROW_ON_ERROR), $vectors, $sources, $enrollment->programTerm->program->code);

        return DB::transaction(function () use ($actor, $enrollment, $studentVector, $scores, $sources, $mlScores, $method, $ranker) {
            $enrollment = StudentEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->embeddings->key($enrollment->student)['source_text_hash'] === $studentVector->source_text_hash, 409, 'Competencies changed during matching. Retry after processing.');
            $profile = $enrollment->student->user?->profile;
            $levels = $enrollment->student->studentCompetencies()->pluck('level', 'competency_id');
            $rows = [];
            foreach ($scores as $id => $score) {
                $opportunity = Opportunity::findOrFail($id);
                $facts = $this->eligibility->inspect($enrollment, $opportunity);
                if (! $facts) {
                    continue;
                }
                abort_unless($this->embeddings->key($opportunity)['source_text_hash'] === $sources[$id]->source_text_hash, 409, 'Opportunity changed during matching. Please retry.');
                $host = $opportunity->hostEstablishment;
                $skills = $opportunity->competencies->map(fn ($skill) => [
                    'name' => $skill->name,
                    'level' => $levels->has($skill->id) && $levels[$skill->id] !== null ? (int) $levels[$skill->id] : null,
                    'minimum_level' => (int) $skill->pivot->minimum_level,
                ])->values()->all();
                $distance = $this->distance($profile, $host);
                $rows[] = ['opportunity_id' => $id, 'similarity_score' => $score, 'distance_km' => $distance,
                    'capacity_at_time' => $facts['capacity_remaining'], 'moa_status_at_time' => $facts['moa_status'],
                    'snapshot' => $facts + ['opportunity_title' => $opportunity->title, 'host_name' => $host->name,
                        'host_id' => $host->id, 'city' => $host->city, 'description' => $opportunity->description, 'tasks' => $opportunity->tasks,
                        'competencies' => $skills,
                        'model_name' => config('matching.model'), 'model_version' => config('matching.revision'),
                        'student_source_hash' => $studentVector->source_text_hash, 'opportunity_source_hash' => $sources[$id]->source_text_hash,
                        'distance_kind' => 'straight_line', 'ranking_method' => $method,
                        'criteria' => self::criteria($method, (bool) ($ranker['synthetic'] ?? false))]
                        + ($method === 'logistic_regression' ? ['suitability_probability' => round($mlScores[$id], 4), 'ranker' => $ranker] : [])];
            }
            usort($rows, fn ($a, $b) => $method === 'logistic_regression'
                ? (($mlScores[$b['opportunity_id']] <=> $mlScores[$a['opportunity_id']]) ?: ($b['similarity_score'] <=> $a['similarity_score']) ?: ($a['opportunity_id'] <=> $b['opportunity_id']))
                : (($b['similarity_score'] <=> $a['similarity_score']) ?: (($a['distance_km'] ?? INF) <=> ($b['distance_km'] ?? INF)) ?: ($a['opportunity_id'] <=> $b['opportunity_id'])));
            $generation = (string) Str::uuid();
            DB::table('recommendation_generations')->insert(['generation_id' => $generation, 'student_enrollment_id' => $enrollment->id, 'created_by' => $actor->id, 'generated_at' => now()]);
            $saved = [];
            foreach ($rows as $index => $row) {
                $saved[] = Recommendation::create($row + ['generation_id' => $generation, 'student_enrollment_id' => $enrollment->id,
                    'rank' => $index + 1, 'ranking_method' => $method, 'generated_at' => now()]);
            }
            Audit::record($actor, 'recommendations.generated', $enrollment, ['generation_id' => $generation, 'count' => count($saved), 'ranking_method' => $method], $enrollment->programTerm->program_id);
            if ($saved !== []) {
                $enrollment->student->user->notify(new PortalNotification('Recommendations available', count($saved).' eligible internship recommendations are ready for review.'));
            }

            return $saved;
        });
    }

    /**
     * Rank eligible candidates. With the reranker on 'auto', the AI service's /recommendations/ml decides:
     * it returns Logistic Regression order only when a validated model is installed, otherwise cosine order.
     * If that endpoint cannot be reached, plain /recommendations (cosine) is used.
     *
     * @return array{0: array<int, float>, 1: array<int, float>, 2: string, 3: array<string, mixed>|null}
     */
    private function rank(array $studentVector, array $vectors, array $sources, string $program): array
    {
        $base = rtrim(config('matching.url'), '/');
        if (config('matching.reranker') === 'auto') {
            try {
                $response = Http::connectTimeout(2)->timeout(7)->post($base.'/recommendations/ml', [
                    'student_vector' => $studentVector, 'program' => $program, 'candidates' => $vectors,
                ])->throw()->json();

                return $this->readRanking($response, $sources, count($vectors));
            } catch (ConnectionException|RequestException $error) {
                Log::warning('matching.reranker_unavailable', ['message' => $error->getMessage()]);
            }
        }
        $response = Http::connectTimeout(2)->timeout(7)->post($base.'/recommendations', [
            'student_vector' => $studentVector,
            'candidates' => array_map(fn (array $candidate) => ['id' => $candidate['id'], 'vector' => $candidate['vector']], $vectors),
        ])->throw()->json();

        return $this->readRanking($response, $sources, count($vectors));
    }

    private function readRanking(?array $response, array $sources, int $expected): array
    {
        abort_unless(($response['model_name'] ?? null) === config('matching.model') && ($response['model_version'] ?? null) === config('matching.revision'), 503, 'AI model version mismatch.');
        $supervised = ($response['ranking_method'] ?? null) === 'logistic_regression';
        $scores = [];
        $mlScores = [];
        foreach ($response['data'] ?? [] as $item) {
            $id = $item['id'] ?? null;
            $score = $item['similarity_score'] ?? null;
            abort_unless(isset($sources[$id]) && ! isset($scores[$id]) && is_numeric($score) && is_finite((float) $score) && $score >= -1 && $score <= 1, 503, 'Invalid AI ranking response.');
            $scores[$id] = (float) $score;
            if ($supervised) {
                $probability = $item['ml_score'] ?? null;
                abort_unless(is_numeric($probability) && $probability >= 0 && $probability <= 1, 503, 'Invalid AI ranking response.');
                $mlScores[$id] = (float) $probability;
            }
        }
        abort_unless(count($scores) === $expected, 503, 'Incomplete AI ranking response.');

        return [$scores, $mlScores, $supervised ? 'logistic_regression' : 'cosine_then_distance', $supervised ? ($response['ranker'] ?? null) : null];
    }

    private function distance(?object $profile, HostEstablishment $host): ?float
    {
        return $profile?->latitude !== null && $profile?->longitude !== null && $host->latitude !== null && $host->longitude !== null
            ? Haversine::kilometers((float) $profile->latitude, (float) $profile->longitude, (float) $host->latitude, (float) $host->longitude) : null;
    }

    /** Plain-language ranking basis stored with each recommendation and shown to coordinators. */
    public static function criteria(string $method, bool $testModel = false): string
    {
        if ($method !== 'logistic_regression') {
            return 'Ordered by skills match, then distance.';
        }

        return 'Ordered by skills match, distance, and past coordinator judgments.'
            .($testModel ? ' Test mode: rankings use practice data, not real coordinator judgments.' : '');
    }
}
