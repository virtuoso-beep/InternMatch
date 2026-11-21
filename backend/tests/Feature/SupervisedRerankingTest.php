<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\StudentCompetency;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\SemanticEmbeddings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Logistic Regression reranking is used only when the AI service reports a validated model; otherwise,
 * and whenever the reranker endpoint fails, recommendations keep cosine order.
 */
class SupervisedRerankingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** A student and two eligible opportunities with cached vectors. */
    private function context(): array
    {
        $student = StudentEnrollment::factory()->create();
        StudentCompetency::factory()->create(['student_id' => $student->student_id]);
        $opportunities = [];
        foreach ([1, 2] as $i) {
            $opportunity = Opportunity::factory()->create(['academic_term_id' => $student->programTerm->academic_term_id, 'status' => 'published']);
            $opportunity->programs()->attach($student->programTerm->program_id, ['capacity' => 2]);
            DB::table('host_program_capacity')->insert(['host_establishment_id' => $opportunity->host_establishment_id, 'program_id' => $student->programTerm->program_id, 'academic_term_id' => $student->programTerm->academic_term_id, 'capacity' => 2]);
            $moa = Moa::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id, 'status' => 'active', 'effective_on' => today()->subDay(), 'expires_on' => today()->addYear()]);
            $moa->programs()->attach($student->programTerm->program_id);
            $this->cache($opportunity);
            $opportunities[] = $opportunity;
        }
        $this->cache($student->student);

        return [$student, ...$opportunities];
    }

    private function cache($owner): void
    {
        DB::table('embeddings')->insert(app(SemanticEmbeddings::class)->key($owner) + ['vector' => json_encode(array_fill(0, 768, 0.1)), 'generated_at' => now()]);
    }

    private function identity(): array
    {
        return ['model_name' => config('matching.model'), 'model_version' => config('matching.revision')];
    }

    private function generate(StudentEnrollment $student)
    {
        return $this->actingAs($student->student->user)->postJson('/api/v1/enrollments/'.$student->id.'/recommendations')->assertOk();
    }

    public function test_validated_model_orders_by_suitability_probability_and_records_it(): void
    {
        [$student, $first, $second] = $this->context();
        Http::fake(['*/recommendations/ml' => Http::response($this->identity() + [
            'ranking_method' => 'logistic_regression',
            'ranker' => ['trained_at' => '2026-11-20T00:00:00+00:00', 'model_sha256' => 'abc'],
            'data' => [
                ['id' => $second->id, 'similarity_score' => 0.61, 'ml_score' => 0.91],
                ['id' => $first->id, 'similarity_score' => 0.88, 'ml_score' => 0.42],
            ],
        ])]);

        $this->generate($student)
            ->assertJsonPath('data.0.opportunity_id', $second->id)->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.ranking_method', 'logistic_regression')
            ->assertJsonPath('data.0.snapshot.suitability_probability', 0.91)
            ->assertJsonPath('data.1.opportunity_id', $first->id);

        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/recommendations/ml')
            && array_key_exists('capacity_at_time', $request['candidates'][0])
            && $request['candidates'][0]['moa_status_at_time'] === 'active');
        Http::assertSentCount(1);
    }

    public function test_service_fallback_keeps_cosine_order(): void
    {
        [$student, $first, $second] = $this->context();
        Http::fake(['*/recommendations/ml' => Http::response($this->identity() + [
            'ranking_method' => 'cosine_fallback',
            'data' => [['id' => $first->id, 'similarity_score' => 0.88], ['id' => $second->id, 'similarity_score' => 0.61]],
        ])]);

        $this->generate($student)
            ->assertJsonPath('data.0.opportunity_id', $first->id)
            ->assertJsonPath('data.0.ranking_method', 'cosine_then_distance')
            ->assertJsonMissingPath('data.0.snapshot.suitability_probability');
    }

    public function test_unreachable_reranker_falls_back_to_plain_cosine(): void
    {
        [$student, $first, $second] = $this->context();
        Http::fake([
            '*/recommendations/ml' => Http::response(['detail' => 'Not Found'], 404),
            '*/recommendations' => Http::response($this->identity() + ['data' => [
                ['id' => $second->id, 'similarity_score' => 0.7], ['id' => $first->id, 'similarity_score' => 0.5],
            ]]),
        ]);

        $this->generate($student)->assertJsonPath('data.0.opportunity_id', $second->id)->assertJsonPath('data.0.ranking_method', 'cosine_then_distance');
        Http::assertSentCount(2);
    }

    public function test_reranker_off_never_calls_the_model(): void
    {
        [$student, $first, $second] = $this->context();
        config(['matching.reranker' => 'off']);
        Http::fake([
            '*/recommendations/ml' => Http::response([], 500),
            '*/recommendations' => Http::response($this->identity() + ['data' => [
                ['id' => $first->id, 'similarity_score' => 0.7], ['id' => $second->id, 'similarity_score' => 0.5],
            ]]),
        ]);
        $this->generate($student);
        Http::assertNotSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/recommendations/ml'));
    }

    public function test_out_of_range_probabilities_are_rejected(): void
    {
        [$student, $first, $second] = $this->context();
        Http::fake(['*/recommendations/ml' => Http::response($this->identity() + ['ranking_method' => 'logistic_regression', 'data' => [
            ['id' => $first->id, 'similarity_score' => 0.7, 'ml_score' => 1.7], ['id' => $second->id, 'similarity_score' => 0.5, 'ml_score' => 0.2],
        ]])]);
        $this->actingAs($student->student->user)->postJson('/api/v1/enrollments/'.$student->id.'/recommendations')->assertStatus(503);
    }

    public function test_status_reports_method_reason_and_judgment_count_to_staff_only(): void
    {
        Http::fake(['*/recommendations/ml/status' => Http::response(['active' => false, 'reason' => 'No trained model is installed.', 'card' => null])]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $this->actingAs($coordinator)->getJson('/api/v1/matching/status')->assertOk()
            ->assertJsonPath('active', false)->assertJsonPath('ranking_method', 'cosine_then_distance')
            ->assertJsonPath('reason', 'No trained model is installed.')->assertJsonStructure(['judgments' => ['total', 'approved']]);

        config(['matching.reranker' => 'off']);
        $this->actingAs(User::factory()->withRole(Role::Dean)->create())->getJson('/api/v1/matching/status')->assertOk()
            ->assertJsonPath('active', false)->assertJsonPath('reranker_setting', 'off');

        foreach ([Role::Student, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/matching/status')->assertForbidden();
        }
    }

    public function test_status_reports_an_active_validated_model(): void
    {
        Http::fake(['*/recommendations/ml/status' => Http::response(['active' => true, 'reason' => null, 'card' => ['n_train' => 400, 'metrics' => ['f1' => 0.81]]])]);
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->getJson('/api/v1/matching/status')->assertOk()
            ->assertJsonPath('active', true)->assertJsonPath('ranking_method', 'logistic_regression')->assertJsonPath('card.n_train', 400);
    }
}
