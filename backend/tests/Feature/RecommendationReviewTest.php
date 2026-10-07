<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Recommendations\Pages\ListRecommendations;
use App\Models\Opportunity;
use App\Models\Recommendation;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\RecommendationReview;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RecommendationReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function recommendation(StudentEnrollment $enrollment): Recommendation
    {
        return Recommendation::create([
            'generation_id' => (string) Str::uuid(), 'student_enrollment_id' => $enrollment->id,
            'opportunity_id' => Opportunity::factory()->create()->id, 'similarity_score' => 0.8,
            'distance_km' => 3.2, 'capacity_at_time' => 2, 'moa_status_at_time' => 'active',
            'rank' => 1, 'ranking_method' => 'cosine_with_placement_criteria',
            'snapshot' => ['model_version' => 'synthetic-test-only'], 'generated_at' => now(),
        ]);
    }

    public function test_native_review_is_scoped_validated_append_only_and_audited(): void
    {
        $enrollment = StudentEnrollment::factory()->create();
        $own = $this->recommendation($enrollment);
        $other = $this->recommendation(StudentEnrollment::factory()->create());
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($enrollment->programTerm->program_id);
        $this->actingAs($actor);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        $component = Livewire::test(ListRecommendations::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$other]);
        $component->callTableAction('review', $own, ['judgment' => 'suitable', 'reason' => 'short', 'confirm' => true])->assertHasTableActionErrors(['reason']);
        Livewire::test(ListRecommendations::class)->callTableAction('review', $own, ['judgment' => 'suitable', 'reason' => 'Synthetic evidence supports these tasks.', 'confirm' => true])->assertHasNoTableActionErrors();
        RecommendationReview::record($actor, $own, ['judgment' => 'uncertain', 'reason' => 'Additional evidence is required for this synthetic case.', 'confirm' => true]);
        $this->assertDatabaseCount('placement_judgments', 2);
        $this->assertDatabaseHas('placement_judgments', ['recommendation_id' => $own->id, 'judgment' => 'suitable', 'approved_by' => $actor->id]);
        $this->assertDatabaseHas('placement_judgments', ['recommendation_id' => $own->id, 'judgment' => 'uncertain']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'recommendation.judgment_recorded', 'actor_id' => $actor->id]);
        $this->assertEquals(0.8, $own->fresh()->similarity_score);
        $this->assertDatabaseCount('placements', 0);
        $this->assertFalse(RecommendationReview::query($actor)->whereKey($other->id)->exists());
    }

    public function test_administrator_has_no_academic_judgment_authority(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $recommendation = $this->recommendation(StudentEnrollment::factory()->create());
        $this->actingAs($admin)->get('/manage/recommendations')->assertForbidden();
        $this->assertFalse(RecommendationReview::query($admin)->exists());
        try {
            RecommendationReview::record($admin, $recommendation, ['judgment' => 'suitable', 'reason' => 'Cannot authorize academic judgment.', 'confirm' => true]);
            $this->fail('Admin judgment must be denied');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertSame(0, DB::table('placement_judgments')->count());
    }

    public function test_react_review_api_preserves_frozen_facts_history_and_program_scope(): void
    {
        $enrollment = StudentEnrollment::factory()->create();
        $own = $this->recommendation($enrollment);
        $other = $this->recommendation(StudentEnrollment::factory()->create());
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($enrollment->programTerm->program_id);
        $before = $own->fresh()->getAttributes();
        $url = '/api/v1/recommendation-reviews/';
        $this->actingAs($actor)->getJson($url)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->postJson($url.$other->id, [])->assertNotFound();
        $this->postJson($url.$own->id, ['judgment' => 'suitable', 'reason' => 'Synthetic test judgment'])->assertUnprocessable();
        foreach (['suitable', 'uncertain'] as $judgment) {
            $this->postJson($url.$own->id, ['judgment' => $judgment, 'reason' => 'Synthetic test judgment, not approved training data.', 'confirm' => true])->assertCreated();
        }
        $this->getJson($url)->assertOk()->assertJsonCount(2, 'data.0.history')->assertJsonPath('data.0.history.0.judgment', 'uncertain');
        $this->assertSame($before, $own->fresh()->getAttributes());
        $this->assertDatabaseCount('placements', 0);
        foreach ([Role::Student, Role::Supervisor, Role::Dean, Role::Admin] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson($url)->assertForbidden();
            $this->postJson($url.$own->id, [])->assertForbidden();
        }
    }
}
