<?php

namespace Tests\Concerns;

use App\Models\Competency;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Models\StudentCompetency;
use App\Models\StudentEnrollment;
use App\Models\UserProfile;
use App\Services\SemanticEmbeddings;
use Illuminate\Support\Facades\DB;

/**
 * Synthetic coordinator labels that the system can score the way live ranking does: the student and the
 * opportunity have current embeddings and coordinates. Vectors are chosen so the cosine is exact:
 * the student is [1, 0, 0, ...] and an opportunity is [a, b, 0, ...], so the cosine is a / sqrt(a² + b²).
 */
trait MakesSyntheticLabels
{
    public const STUDENT_AT = [7.0731, 125.6128];

    /** Give a synthetic student a listed skill without a level (as in the source data), coordinates and an embedding. */
    protected function scorableStudent(StudentEnrollment $enrollment, ?Competency $skill = null): void
    {
        StudentCompetency::factory()->create(['student_id' => $enrollment->student_id, 'competency_id' => ($skill ?? Competency::factory()->create())->id, 'level' => null]);
        UserProfile::updateOrCreate(['user_id' => $enrollment->student->user_id], ['latitude' => self::STUDENT_AT[0], 'longitude' => self::STUDENT_AT[1]]);
        $this->embed($enrollment->student->fresh(), [1, 0]);
    }

    /** An opportunity at the given coordinates whose embedding is [a, b, 0, ...]. */
    protected function scorableOpportunity(array $direction, array $hostAt, array $skills = []): Opportunity
    {
        $host = HostEstablishment::factory()->create(['latitude' => $hostAt[0], 'longitude' => $hostAt[1]]);
        $opportunity = Opportunity::factory()->create(['host_establishment_id' => $host->id]);
        foreach ($skills as $skill) {
            $opportunity->competencies()->attach($skill->id, ['minimum_level' => 0, 'is_required' => true]);
        }
        $this->embed($opportunity->fresh(), $direction);

        return $opportunity;
    }

    /** A coordinator_labels row. The source file's own skill_match_score and distance_km must never be used. */
    protected function syntheticLabel(StudentEnrollment $enrollment, ?Opportunity $opportunity, ?bool $suitable, string $source): array
    {
        return ['source_id' => $source, 'student_id' => $enrollment->student_id, 'program_id' => $enrollment->programTerm->program_id,
            'hte_code' => 'HTE-X', 'opportunity_code' => 'OPP-X', 'opportunity_id' => $opportunity?->id, 'program_match' => 1,
            'skill_match_score' => 0.1234, 'distance_km' => 999, 'available_slots' => 2, 'has_slot' => 1, 'moa_valid' => 1,
            'coordinator_decision' => $suitable === null ? 'For Review' : ($suitable ? 'Suitable' : 'Not Suitable'),
            'suitable' => $suitable, 'is_synthetic' => true];
    }

    private function embed(object $owner, array $direction): void
    {
        $vector = array_pad($direction, 768, 0);
        DB::table('embeddings')->insert(app(SemanticEmbeddings::class)->key($owner) + ['vector' => json_encode($vector), 'generated_at' => now()]);
    }
}
