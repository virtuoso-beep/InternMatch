<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Recommendation;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        foreach ($candidates as $opportunity) {
            if (! $this->eligibility->inspect($enrollment, $opportunity)) { continue; }
            $cached = $this->embeddings->cached($opportunity);
            abort_unless($cached, 409, 'Eligible opportunity embeddings are still processing. Please retry shortly.');
            $vectors[] = ['id' => $opportunity->id, 'vector' => json_decode($cached->vector, true, flags: JSON_THROW_ON_ERROR)];
            $sources[$opportunity->id] = $cached;
        }
        abort_if(count($vectors) > 1000, 422, 'This cohort exceeds the configured matching batch limit.');
        $scores = [];
        if ($vectors !== []) {
            $response = Http::connectTimeout(2)->timeout(7)->post(rtrim(config('matching.url'), '/').'/recommendations', [
                'student_vector' => json_decode($studentVector->vector, true, flags: JSON_THROW_ON_ERROR), 'candidates' => $vectors,
            ])->throw()->json();
            abort_unless(($response['model_name'] ?? null) === config('matching.model') && ($response['model_version'] ?? null) === config('matching.revision'), 503, 'AI model version mismatch.');
            foreach ($response['data'] ?? [] as $item) {
                $id = $item['id'] ?? null;
                $score = $item['similarity_score'] ?? null;
                abort_unless(isset($sources[$id]) && ! isset($scores[$id]) && is_numeric($score) && is_finite((float) $score) && $score >= -1 && $score <= 1, 503, 'Invalid AI ranking response.');
                $scores[$id] = (float) $score;
            }
            abort_unless(count($scores) === count($vectors), 503, 'Incomplete AI ranking response.');
        }
        return DB::transaction(function () use ($actor, $enrollment, $studentVector, $scores, $sources) {
            $enrollment = StudentEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->embeddings->key($enrollment->student)['source_text_hash'] === $studentVector->source_text_hash, 409, 'Competencies changed during matching. Retry after processing.');
            $profile = $enrollment->student->user->profile;
            $rows = [];
            foreach ($scores as $id => $score) {
                $opportunity = Opportunity::findOrFail($id);
                $facts = $this->eligibility->inspect($enrollment, $opportunity);
                if (! $facts) { continue; }
                abort_unless($this->embeddings->key($opportunity)['source_text_hash'] === $sources[$id]->source_text_hash, 409, 'Opportunity changed during matching. Please retry.');
                $host = $opportunity->hostEstablishment;
                $distance = $profile?->latitude !== null && $profile?->longitude !== null && $host->latitude !== null && $host->longitude !== null
                    ? Haversine::kilometers((float) $profile->latitude, (float) $profile->longitude, (float) $host->latitude, (float) $host->longitude) : null;
                $rows[] = ['opportunity_id' => $id, 'similarity_score' => $score, 'distance_km' => $distance,
                    'capacity_at_time' => $facts['capacity_remaining'], 'moa_status_at_time' => $facts['moa_status'],
                    'snapshot' => $facts + ['opportunity_title' => $opportunity->title, 'host_name' => $host->name,
                        'host_id' => $host->id, 'model_name' => config('matching.model'), 'model_version' => config('matching.revision'),
                        'student_source_hash' => $studentVector->source_text_hash, 'opportunity_source_hash' => $sources[$id]->source_text_hash,
                        'distance_kind' => 'straight_line', 'criteria' => 'Cosine descending; ties use known distance ascending, then opportunity ID. No requirement-completion ranking feature.']];
            }
            usort($rows, fn ($a, $b) => ($b['similarity_score'] <=> $a['similarity_score']) ?: (($a['distance_km'] ?? INF) <=> ($b['distance_km'] ?? INF)) ?: ($a['opportunity_id'] <=> $b['opportunity_id']));
            $generation = (string) Str::uuid();
            DB::table('recommendation_generations')->insert(['id' => $generation, 'student_enrollment_id' => $enrollment->id, 'created_by' => $actor->id, 'generated_at' => now()]);
            $saved = [];
            foreach ($rows as $index => $row) {
                $saved[] = Recommendation::create($row + ['generation_id' => $generation, 'student_enrollment_id' => $enrollment->id,
                    'rank' => $index + 1, 'ranking_method' => 'cosine_then_distance', 'generated_at' => now()]);
            }
            Audit::record($actor, 'recommendations.generated', $enrollment, ['generation_id' => $generation, 'count' => count($saved)], $enrollment->programTerm->program_id);
            if ($saved !== []) { $enrollment->student->user->notify(new PortalNotification('Recommendations available', count($saved).' eligible internship recommendations are ready for review.')); }
            return $saved;
        });
    }
}
