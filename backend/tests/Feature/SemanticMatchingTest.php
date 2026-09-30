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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SemanticMatchingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function context(): array
    {
        $student = StudentEnrollment::factory()->create();
        StudentCompetency::factory()->create(['student_id' => $student->student_id]);
        $opportunity = Opportunity::factory()->create(['academic_term_id' => $student->programTerm->academic_term_id, 'status' => 'published']);
        $opportunity->programs()->attach($student->programTerm->program_id, ['capacity' => 2]);
        DB::table('host_program_capacity')->insert(['host_establishment_id' => $opportunity->host_establishment_id, 'program_id' => $student->programTerm->program_id, 'academic_term_id' => $student->programTerm->academic_term_id, 'capacity' => 2]);
        $moa = Moa::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id, 'status' => 'active', 'effective_on' => today()->subDay(), 'expires_on' => today()->addYear()]);
        $moa->programs()->attach($student->programTerm->program_id);

        return [$student, $opportunity];
    }

    private function cache($owner): void
    {
        DB::table('embeddings')->insert(app(SemanticEmbeddings::class)->key($owner) + ['vector' => json_encode(array_fill(0, 768, 0.1)), 'generated_at' => now()]);
    }

    public function test_generation_filters_first_freezes_facts_notifies_and_records_empty_generations(): void
    {
        [$student, $opportunity] = $this->context();
        $this->cache($student->student);
        $this->cache($opportunity);
        Http::fake(['*/recommendations' => Http::response(['data' => [['id' => $opportunity->id, 'similarity_score' => 0.83]], 'model_name' => config('matching.model'), 'model_version' => config('matching.revision')])]);
        $url = '/api/v1/enrollments/'.$student->id.'/recommendations';
        $this->actingAs($student->student->user)->postJson($url)->assertOk()->assertJsonPath('data.0.similarity_score', 0.83)->assertJsonPath('data.0.capacity_at_time', 2);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('recommendations', 1);
        $opportunity->update(['status' => 'closed']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.capacity_at_time', 2);
        $this->postJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('recommendation_generations', 2);
        Http::assertSentCount(1);
    }

    public function test_missing_or_stale_vectors_and_cross_student_generation_are_rejected(): void
    {
        [$student, $opportunity] = $this->context();
        Http::preventStrayRequests();
        $url = '/api/v1/enrollments/'.$student->id.'/recommendations';
        $this->actingAs($student->student->user)->postJson($url)->assertConflict();
        $this->cache($student->student);
        $this->cache($opportunity);
        $opportunity->update(['tasks' => 'Changed task text']);
        $this->postJson($url)->assertConflict();
        $this->actingAs(User::factory()->withRole(Role::Student)->create())->postJson($url)->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson($url)->assertForbidden();
        $this->assertDatabaseCount('recommendations', 0);
    }

    public function test_embedding_cache_verifies_prefix_model_hash_and_reuses_valid_provenance(): void
    {
        [$student] = $this->context();
        $service = app(SemanticEmbeddings::class);
        $key = $service->key($student->student);
        Http::fake(['*/embeddings' => Http::response($key + ['vector' => array_fill(0, 768, 0.1)])]);
        $service->refresh($student->student);
        $service->refresh($student->student);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['kind'] === 'student');
        $this->assertNotNull($service->cached($student->student));
        $this->assertDatabaseCount('embeddings', 1);
    }

    public function test_interest_is_owned_persistent_and_cannot_bypass_eligibility_or_closed_enrollment(): void
    {
        [$student, $opportunity] = $this->context();
        $url = '/api/v1/enrollments/'.$student->id.'/opportunities/'.$opportunity->id.'/interest';
        $this->actingAs($student->student->user)->postJson($url, ['interested' => false])->assertNotFound();
        $this->postJson($url, ['interested' => true])->assertNoContent();
        $this->getJson('/api/v1/enrollments/'.$student->id.'/interests')->assertJsonPath('data.0', $opportunity->id);
        $this->postJson($url, ['interested' => false])->assertNoContent();
        $this->getJson('/api/v1/enrollments/'.$student->id.'/interests')->assertJsonCount(0, 'data');
        $opportunity->update(['status' => 'closed']);
        $this->postJson($url, ['interested' => true])->assertUnprocessable();
        $student->update(['status' => 'withdrawn']);
        $this->postJson($url, ['interested' => false])->assertConflict();
        $this->actingAs(User::factory()->withRole(Role::Student)->create())->postJson($url, ['interested' => true])->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson($url, ['interested' => true])->assertForbidden();
    }
}
