<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EvaluationWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function context(): array
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => '2026-09-01']);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($placement->studentEnrollment->programTerm->program_id);

        return [$placement, $supervisor, $coordinator];
    }

    private function rubric(Placement $placement, User $coordinator): array
    {
        return $this->actingAs($coordinator)->postJson('/api/v1/programs/'.$placement->studentEnrollment->programTerm->program_id.'/evaluation-rubrics', [
            'name' => 'QA rubric', 'approval_reference' => 'Test fixture only',
            'criteria' => [['name' => 'Work quality', 'max_score' => 5, 'weight' => 3], ['name' => 'Communication', 'max_score' => 10, 'weight' => 1]],
        ])->assertCreated()->json('data');
    }

    public function test_weighted_scoring_preserves_the_rubric_version_and_notifies_on_submission_only(): void
    {
        [$placement, $supervisor, $coordinator] = $this->context();
        $rubric = $this->rubric($placement, $coordinator);
        $payload = ['period' => 'Final', 'status' => 'draft', 'scores' => [['criterion_id' => $rubric['criteria'][0]['id'], 'score' => 4], ['criterion_id' => $rubric['criteria'][1]['id'], 'score' => 6]]];
        $this->actingAs($supervisor)->postJson('/api/v1/placements/'.$placement->id.'/evaluations', $payload)->assertOk()->assertJsonPath('data.weighted_percentage', 75);
        $student = $placement->studentEnrollment->student->user;
        $this->actingAs($student)->getJson('/api/v1/placements/'.$placement->id.'/evaluations')->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('notifications', 0);
        $next = $this->rubric($placement, $coordinator);
        $this->assertSame(2, $next['version']);
        $this->actingAs($supervisor)->postJson('/api/v1/placements/'.$placement->id.'/evaluations', [...$payload, 'status' => 'submitted'])->assertOk()->assertJsonPath('data.evaluation_rubric_id', $rubric['id'])->assertJsonPath('data.weighted_percentage', 75);
        $this->postJson('/api/v1/placements/'.$placement->id.'/evaluations', $payload)->assertForbidden();
        $this->actingAs($student)->getJson('/api/v1/placements/'.$placement->id.'/evaluations')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread_count', 1);
    }

    public function test_missing_rubrics_out_of_range_foreign_and_incomplete_scores_cannot_be_submitted(): void
    {
        [$placement, $supervisor, $coordinator] = $this->context();
        $url = '/api/v1/placements/'.$placement->id.'/evaluations';
        $payload = ['period' => 'Midterm', 'status' => 'submitted', 'scores' => [['criterion_id' => 1, 'score' => 4]]];
        $this->actingAs($supervisor)->postJson($url, $payload)->assertUnprocessable();
        $rubric = $this->rubric($placement, $coordinator);
        $this->actingAs($supervisor)->postJson($url, [...$payload, 'scores' => [['criterion_id' => $rubric['criteria'][0]['id'], 'score' => 4]]])->assertUnprocessable();
        $this->postJson($url, [...$payload, 'scores' => [['criterion_id' => $rubric['criteria'][0]['id'], 'score' => 6]]])->assertUnprocessable();
        $this->postJson($url, [...$payload, 'scores' => [['criterion_id' => 999999, 'score' => 4]]])->assertUnprocessable();
        $this->assertDatabaseCount('evaluations', 0);
        $this->actingAs($coordinator)->postJson($url, $payload)->assertForbidden();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson($url, $payload)->assertForbidden();
        $other = User::factory()->withRole(Role::Supervisor)->create();
        $other->hostEstablishments()->attach($placement->host_establishment_id);
        $this->actingAs($other)->getJson($url)->assertNotFound();
        $this->postJson($url, $payload)->assertNotFound();
    }

    public function test_program_configuration_requires_scope_and_confirmation_reference(): void
    {
        [$placement, , $coordinator] = $this->context();
        $url = '/api/v1/program-terms/'.$placement->studentEnrollment->program_term_id.'/monitoring-rules';
        $this->actingAs($coordinator)->putJson($url, ['remaining_days_threshold' => 14, 'remaining_hours_threshold' => 80])->assertUnprocessable();
        $data = ['remaining_days_threshold' => 14, 'remaining_hours_threshold' => 80, 'approval_reference' => 'QA fixture'];
        $this->putJson($url, $data)->assertOk()->assertJsonPath('data.monitoring_rules.remaining_hours_threshold', 80);
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create())->putJson($url, $data)->assertForbidden();
        $this->getJson('/api/v1/program-monitoring')->assertJsonCount(0, 'programs');
    }
}
