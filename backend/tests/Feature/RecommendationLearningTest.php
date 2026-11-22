<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Program;
use App\Models\StudentCompetency;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\RecommendationLearning;
use App\Services\SemanticEmbeddings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Recommendation learning (spec §9): live counts, the 30/30 rule, run history, roles and test data. */
class RecommendationLearningTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin)->create();
    }

    /** One recommendation plus one approved judgment for a student of the given enrollment. */
    private function judge(StudentEnrollment $enrollment, string $judgment, bool $approved = true, float $similarity = 0.8, ?User $by = null): int
    {
        $by ??= User::factory()->withRole(Role::Coordinator)->create();
        $rec = DB::table('recommendations')->insertGetId(['generation_id' => (string) Str::uuid(), 'student_enrollment_id' => $enrollment->id,
            'opportunity_id' => Opportunity::factory()->create()->id, 'similarity_score' => $similarity, 'distance_km' => 4, 'capacity_at_time' => 2,
            'moa_status_at_time' => 'active', 'rank' => 1, 'ranking_method' => 'cosine_then_distance',
            'snapshot' => json_encode(['competencies' => [['name' => 'A', 'level' => 80], ['name' => 'B', 'level' => null]]]), 'generated_at' => now()]);
        $this->judgeRecommendation($rec, $judgment, $approved, $by);

        return $rec;
    }

    private function judgeRecommendation(int $rec, string $judgment, bool $approved = true, ?User $by = null): void
    {
        $by ??= User::factory()->withRole(Role::Coordinator)->create();
        DB::table('placement_judgments')->insert(['recommendation_id' => $rec, 'coordinator_id' => $by->id, 'judgment' => $judgment, 'reason' => 'test',
            'approved_by' => $approved ? $by->id : null, 'approved_at' => $approved ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** $students real students, each with 5 judged pairs; $suitablePerStudent of each student's pairs are suitable. */
    private function cohort(int $students, int $suitablePerStudent = 2): StudentEnrollment
    {
        $first = StudentEnrollment::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        for ($s = 0; $s < $students; $s++) {
            $enrollment = $s === 0 ? $first : StudentEnrollment::factory()->create(['program_term_id' => $first->program_term_id]);
            for ($pair = 0; $pair < 5; $pair++) {
                $this->judge($enrollment, $pair < $suitablePerStudent ? 'suitable' : 'unsuitable', true, $pair < $suitablePerStudent ? 0.85 : 0.4, $coordinator);
            }
        }

        return $first;
    }

    public function test_counts_are_live_latest_approved_per_pair_excluding_test_students_and_uncertain(): void
    {
        $real = StudentEnrollment::factory()->create();
        $test = StudentEnrollment::factory()->create(['program_term_id' => $real->program_term_id, 'is_synthetic' => true]);
        $changed = $this->judge($real, 'suitable');
        $this->judgeRecommendation($changed, 'unsuitable');   // the latest judgment of a pair counts
        $this->judge($real, 'uncertain');                      // counted, but never trained on
        $this->judge($test, 'suitable');                       // test student: never counted
        $this->judge($real, 'suitable', approved: false);      // not approved: never counted

        $learning = app(RecommendationLearning::class);
        $this->assertSame(['suitable' => 0, 'not_suitable' => 1, 'uncertain' => 1], $learning->counts()['overall']);
        $this->judge($real, 'suitable');
        $this->assertSame(1, $learning->counts()['overall']['suitable']); // live, not cached
        $this->assertCount(2, $learning->realRows());
    }

    public function test_admin_sees_everything_and_coordinators_see_only_their_programs_read_only(): void
    {
        Http::fake(['*/recommendations/ml/status' => Http::response(['active' => false, 'reason' => 'No trained model is installed.', 'card' => null])]);
        $own = StudentEnrollment::factory()->create();
        $other = StudentEnrollment::factory()->create();
        $this->judge($own, 'suitable');
        $this->judge($other, 'unsuitable');
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($own->programTerm->program_id);
        $empty = Program::factory()->create();
        $coordinator->programs()->attach($empty->id);

        $this->actingAs($this->admin())->getJson('/api/v1/recommendation-learning')->assertOk()
            ->assertJsonPath('role', 'admin')->assertJsonPath('ranking.mode', 'similarity')
            ->assertJsonPath('counts.overall.suitable', 1)->assertJsonPath('counts.overall.not_suitable', 1)->assertJsonPath('counts.required', 30)
            ->assertJsonPath('technical.fallback', null)->assertJsonPath('history', []);

        $response = $this->actingAs($coordinator)->getJson('/api/v1/recommendation-learning')->assertOk()->assertJsonPath('role', 'coordinator');
        $this->assertEquals(collect([$empty->code => ['suitable' => 0, 'not_suitable' => 0, 'uncertain' => 0],
            $own->programTerm->program->code => ['suitable' => 1, 'not_suitable' => 0, 'uncertain' => 0]])->sortKeys()->all(),
            $response->json('counts.by_program')); // every assigned program, with zeros when it has no judgments
        $this->assertArrayNotHasKey($other->programTerm->program->code, $response->json('counts.by_program'));
        $response->assertJsonMissingPath('history')->assertJsonMissingPath('technical');
        $this->postJson('/api/v1/recommendation-learning/update')->assertForbidden();

        foreach ([Role::Dean, Role::Student, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/recommendation-learning')->assertForbidden();
        }
    }

    public function test_too_few_judgments_are_recorded_without_training(): void
    {
        Http::fake();
        $this->cohort(students: 3);
        $admin = $this->admin();
        $this->actingAs($admin)->postJson('/api/v1/recommendation-learning/update')->assertOk()
            ->assertJsonPath('data.result', 'not_enough_data')->assertJsonPath('data.suitable', 6)->assertJsonPath('data.not_suitable', 9);
        Http::assertNotSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/ml/train'));
        $this->assertDatabaseHas('ranker_runs', ['result' => 'not_enough_data', 'source' => 'admin', 'user_id' => $admin->id, 'used_test_data' => false]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'recommendations.learning_run']);
    }

    public function test_enough_judgments_are_trained_and_every_outcome_is_recorded(): void
    {
        $this->cohort(students: 15); // 30 suitable, 45 not suitable
        $report = fn (string $result) => ['result' => $result, 'reason' => $result === 'updated' ? null : 'Leave-one-student-out F1 0.600 is not better than similarity alone (0.640).',
            'counts' => ['suitable' => 30, 'not_suitable' => 45], 'fallback' => null,
            'metrics' => ['model' => ['overall' => ['accuracy' => 0.8, 'precision' => 0.7, 'recall' => 0.9, 'f1' => 0.79, 'n' => 75], 'by_program' => [], 'students' => 15],
                'similarity_only' => ['overall' => ['accuracy' => 0.7, 'precision' => 0.6, 'recall' => 0.8, 'f1' => 0.69, 'n' => 75], 'by_program' => [], 'students' => 15]],
            'model' => $result === 'updated' ? ['trained_at' => '2026-11-22T01:00:00+00:00', 'model_sha256' => str_repeat('a', 64), 'synthetic' => false] : null];
        Http::fake(['*/ml/train' => Http::sequence()->push($report('updated'))->push($report('not_reliable'))->push(['detail' => 'boom'], 500)]);
        $this->actingAs($this->admin());

        $this->postJson('/api/v1/recommendation-learning/update')->assertOk()->assertJsonPath('data.result', 'updated');
        Http::assertSent(function (ClientRequest $request) {
            if (! str_ends_with($request->url(), '/ml/train')) {
                return false;
            }
            $row = $request['rows'][0];

            return count($request['rows']) === 75 && $request['min_per_class'] === 30 && $row['synthetic'] === false
                && array_keys($row) === ['student', 'program', 'similarity', 'distance_km', 'keyword_overlap', 'label', 'synthetic']
                && $row['keyword_overlap'] === 0.5;
        });
        $this->assertDatabaseHas('ranker_runs', ['result' => 'updated', 'model_sha256' => str_repeat('a', 64), 'suitable' => 30, 'not_suitable' => 45, 'students' => 15]);

        $this->postJson('/api/v1/recommendation-learning/update')->assertOk()->assertJsonPath('data.result', 'not_reliable')
            ->assertJsonPath('data.metrics.similarity_only.overall.f1', 0.69);
        $this->postJson('/api/v1/recommendation-learning/update')->assertOk()->assertJsonPath('data.result', 'failed');
        $this->assertSame(['failed', 'not_reliable', 'updated'], DB::table('ranker_runs')->orderByDesc('id')->pluck('result')->all());
        $this->getJson('/api/v1/recommendation-learning')->assertJsonCount(3, 'history')->assertJsonPath('last_run.result', 'failed');
    }

    public function test_test_data_needs_local_test_mode_and_never_counts_as_real_judgments(): void
    {
        config(['matching.allow_test_model' => false]);
        $this->actingAs($this->admin())->postJson('/api/v1/recommendation-learning/update', ['test_data' => true])->assertUnprocessable();
        $this->artisan('ml:retrain', ['--test-data' => true])->assertExitCode(2);

        $program = StudentEnrollment::factory()->create(['is_synthetic' => true]);
        $labels = [];
        for ($i = 0; $i < 80; $i++) {
            $labels[] = ['source_id' => 'LBL-'.$i, 'student_id' => $program->student_id, 'program_id' => $program->programTerm->program_id, 'hte_code' => 'H',
                'opportunity_code' => 'O', 'program_match' => 1, 'skill_match_score' => $i % 2 ? .8 : .3, 'distance_km' => 5, 'available_slots' => 2,
                'has_slot' => 1, 'moa_valid' => 1, 'coordinator_decision' => 'x', 'suitable' => (bool) ($i % 2), 'is_synthetic' => true];
        }
        DB::table('coordinator_labels')->insert($labels);
        config(['matching.allow_test_model' => true]);
        Http::fake(['*/ml/train' => Http::response(['result' => 'not_reliable', 'reason' => 'x', 'counts' => [], 'metrics' => null, 'fallback' => null])]);
        $this->postJson('/api/v1/recommendation-learning/update', ['test_data' => true])->assertOk()->assertJsonPath('data.used_test_data', true)
            ->assertJsonPath('data.suitable', 30)->assertJsonPath('data.not_suitable', 30); // capped at 60 per program, balanced
        Http::assertSent(fn (ClientRequest $request) => collect($request['rows'])->every(fn ($row) => $row['synthetic'] === true));
        $this->assertSame(['suitable' => 0, 'not_suitable' => 0, 'uncertain' => 0], app(RecommendationLearning::class)->counts()['overall']);
    }

    public function test_command_runs_the_same_action_and_overlapping_runs_are_refused(): void
    {
        $this->cohort(students: 2);
        $this->artisan('ml:retrain')->expectsOutputToContain('there are not enough judgments yet')->assertSuccessful();
        $this->assertDatabaseHas('ranker_runs', ['source' => 'command', 'user_id' => null, 'result' => 'not_enough_data']);

        $lock = Cache::lock('recommendation-learning', 60);
        $lock->get();
        $this->actingAs($this->admin())->postJson('/api/v1/recommendation-learning/update')->assertStatus(409);
        $lock->release();
    }

    public function test_fallback_evaluation_by_hand(): void
    {
        $row = fn (string $student, float $similarity, ?float $distance, float $overlap, int $label) => compact('student', 'similarity', 'overlap', 'label')
            + ['distance_km' => $distance, 'keyword_overlap' => $overlap, 'program' => 'BSA'];
        $rows = [$row('A', .9, 9, .2, 1), $row('A', .8, 1, .9, 0), $row('A', .7, 2, .8, 1), $row('A', .2, 3, .1, 0), $row('B', .5, 3, .1, 1)];
        $result = app(RecommendationLearning::class)->fallbackEvaluation($rows);
        $this->assertSame(1, $result['students']); // B has no not-suitable label
        $this->assertEqualsWithDelta(2 / 3, $result['similarity']['precision_at_3'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['similarity']['recall_at_3'], 1e-9);
        $this->assertEqualsWithDelta((1 + 1 / 2) / (1 + 1 / log(3, 2)), $result['similarity']['ndcg_at_3'], 1e-9);
        $this->assertEqualsWithDelta(1 / 3, $result['distance_only']['precision_at_3'], 1e-9);
        $this->assertNull(app(RecommendationLearning::class)->fallbackEvaluation([$rows[4]]));
    }

    public function test_ineligible_opportunities_never_reach_ranking(): void
    {
        $student = StudentEnrollment::factory()->create();
        StudentCompetency::factory()->create(['student_id' => $student->student_id]);
        $make = function (array $opportunity = [], array $moa = [], int $capacity = 2) use ($student) {
            $item = Opportunity::factory()->create($opportunity + ['academic_term_id' => $student->programTerm->academic_term_id, 'status' => 'published']);
            $item->programs()->attach($student->programTerm->program_id, ['capacity' => $capacity]);
            DB::table('host_program_capacity')->insert(['host_establishment_id' => $item->host_establishment_id, 'program_id' => $student->programTerm->program_id, 'academic_term_id' => $student->programTerm->academic_term_id, 'capacity' => 2]);
            Moa::factory()->create($moa + ['host_establishment_id' => $item->host_establishment_id, 'status' => 'active', 'effective_on' => today()->subYear(), 'expires_on' => today()->addYear()])
                ->programs()->attach($student->programTerm->program_id);
            DB::table('embeddings')->insert(app(SemanticEmbeddings::class)->key($item) + ['vector' => json_encode(array_fill(0, 768, 0.1)), 'generated_at' => now()]);

            return $item;
        };
        $eligible = $make();
        $make(moa: ['expires_on' => today()->subDay()]);   // expired agreement
        $make(capacity: 0);                                 // no slot left for the program
        $make(opportunity: ['status' => 'closed']);         // not active
        DB::table('embeddings')->insert(app(SemanticEmbeddings::class)->key($student->student) + ['vector' => json_encode(array_fill(0, 768, 0.1)), 'generated_at' => now()]);
        Http::fake(['*/recommendations/ml' => Http::response(['model_name' => config('matching.model'), 'model_version' => config('matching.revision'),
            'ranking_method' => 'cosine_fallback', 'data' => [['id' => $eligible->id, 'similarity_score' => 0.7]]])]);

        $this->actingAs($student->student->user)->postJson('/api/v1/enrollments/'.$student->id.'/recommendations')->assertOk()->assertJsonCount(1, 'data');
        Http::assertSent(fn (ClientRequest $request) => array_column($request['candidates'], 'id') === [$eligible->id]);
    }
}
