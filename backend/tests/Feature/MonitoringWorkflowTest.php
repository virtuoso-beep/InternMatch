<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\User;
use App\Services\ProgressSummary;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MonitoringWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function context(): array
    {
        $this->travelTo(now()->setDate(2026, 9, 19)->setTime(12, 0));
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => '2026-09-01', 'ends_on' => '2026-09-30']);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $placement->studentEnrollment->programTerm->program->update(['required_ojt_hours' => 100]);

        return [$placement, $supervisor, $placement->studentEnrollment->student->user];
    }

    public function test_time_submission_verification_and_program_progress_are_persisted(): void
    {
        [$placement, $supervisor, $student] = $this->context();
        $payload = ['time_in' => '2026-09-18T08:00:00+08:00', 'time_out' => '2026-09-18T17:00:00+08:00', 'break_minutes' => 60];
        $id = $this->actingAs($student)->postJson('/api/v1/placements/'.$placement->id.'/time-logs', $payload)->assertCreated()->assertJsonPath('data.credited_minutes', null)->json('data.id');
        $this->getJson('/api/v1/placements/'.$placement->id.'/monitoring')->assertJsonPath('progress.completed_minutes', 0);
        $this->postJson('/api/v1/time-logs/'.$id.'/verify', ['status' => 'verified'])->assertForbidden();
        $this->actingAs($supervisor)->postJson('/api/v1/time-logs/'.$id.'/verify', ['status' => 'verified', 'credited_minutes' => 1000])->assertUnprocessable();
        $this->postJson('/api/v1/time-logs/'.$id.'/verify', ['status' => 'verified'])->assertOk()->assertJsonPath('data.credited_minutes', 480);
        $this->getJson('/api/v1/placements/'.$placement->id.'/monitoring')->assertJsonPath('progress.completed_minutes', 480)->assertJsonPath('progress.remaining_minutes', 5520)->assertJsonPath('progress.required_hours', 100);
        $this->actingAs($student)->postJson('/api/v1/placements/'.$placement->id.'/time-logs', [...$payload, 'id' => $id])->assertConflict();
        $this->assertDatabaseCount('time_logs', 1);
    }

    public function test_time_overlap_dates_and_cross_scope_access_are_rejected(): void
    {
        [$placement, $supervisor, $student] = $this->context();
        $payload = ['time_in' => '2026-09-18T08:00:00+08:00', 'time_out' => '2026-09-18T17:00:00+08:00', 'break_minutes' => 60];
        $id = $this->actingAs($student)->postJson('/api/v1/placements/'.$placement->id.'/time-logs', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/placements/'.$placement->id.'/time-logs', [...$payload, 'time_in' => '2026-09-18T16:00:00+08:00', 'break_minutes' => 0])->assertUnprocessable();
        $this->postJson('/api/v1/placements/'.$placement->id.'/time-logs', [...$payload, 'break_minutes' => 540])->assertUnprocessable();
        $this->postJson('/api/v1/placements/'.$placement->id.'/time-logs', [...$payload, 'time_out' => '2026-09-20T17:00:00+08:00'])->assertUnprocessable();
        foreach ([Role::Student, Role::Coordinator, Role::Supervisor, Role::Dean] as $role) {
            $outsider = User::factory()->withRole($role)->create();
            $this->actingAs($outsider)->getJson('/api/v1/placements/'.$placement->id.'/monitoring')->assertNotFound();
            $this->postJson('/api/v1/time-logs/'.$id.'/verify', ['status' => 'verified'])->assertForbidden();
        }
        $supervisor->hostEstablishments()->detach();
        $this->actingAs($supervisor)->getJson('/api/v1/placements')->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/time-logs/'.$id.'/verify', ['status' => 'verified'])->assertForbidden();
    }

    public function test_journal_draft_submit_review_and_resubmission_follow_state_rules(): void
    {
        [$placement, $supervisor, $student] = $this->context();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($placement->studentEnrollment->programTerm->program_id);
        $payload = ['week_starts_on' => '2026-09-14', 'content' => 'Prepared and documented assigned tasks.', 'status' => 'draft'];
        $id = $this->actingAs($student)->postJson('/api/v1/placements/'.$placement->id.'/journals', $payload)->assertOk()->json('data.id');
        $this->postJson('/api/v1/placements/'.$placement->id.'/journals', [...$payload, 'status' => 'submitted'])->assertOk();
        $this->postJson('/api/v1/placements/'.$placement->id.'/journals', $payload)->assertConflict();
        $this->actingAs($supervisor)->postJson('/api/v1/journals/'.$id.'/review', ['status' => 'approved'])->assertForbidden();
        $this->actingAs($coordinator)->postJson('/api/v1/journals/'.$id.'/review', ['status' => 'rejected', 'comments' => 'Describe the completed tasks.'])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/placements/'.$placement->id.'/journals', [...$payload, 'content' => 'Updated journal with completed tasks.', 'status' => 'submitted'])->assertOk();
        $this->actingAs($coordinator)->postJson('/api/v1/journals/'.$id.'/review', ['status' => 'approved'])->assertOk();
        $this->assertDatabaseCount('journal_entries', 1);
        $this->actingAs($student)->getJson('/api/v1/notifications')->assertJsonPath('unread_count', 2);
    }

    public function test_risk_boundaries_use_current_program_hours_and_latest_document_revision(): void
    {
        [$placement] = $this->context();
        $term = $placement->studentEnrollment->programTerm;
        $term->update(['monitoring_rules' => ['remaining_days_threshold' => 11, 'remaining_hours_threshold' => 100]]);
        $service = app(ProgressSummary::class);
        $this->assertCount(1, $service->forPlacement($placement)['flags']);
        $term->update(['monitoring_rules' => ['remaining_days_threshold' => 10, 'remaining_hours_threshold' => 100]]);
        $this->assertCount(0, $service->forPlacement($placement)['flags']);
        $term->program->update(['required_ojt_hours' => null]);
        $this->assertNull($service->forPlacement($placement)['remaining_minutes']);
        $requirement = ProgramTermRequirement::factory()->create(['program_term_id' => $term->id, 'due_at' => now()]);
        $this->assertCount(0, $service->forPlacement($placement)['flags']);
        $this->travel(1)->seconds();
        $this->assertCount(1, $service->forPlacement($placement)['flags']);
        RequirementSubmission::factory()->create(['program_term_id' => $term->id, 'program_term_requirement_id' => $requirement->id, 'student_enrollment_id' => $placement->student_enrollment_id, 'revision' => 1, 'status' => 'approved']);
        $this->assertCount(0, $service->forPlacement($placement)['flags']);
        RequirementSubmission::factory()->create(['program_term_id' => $term->id, 'program_term_requirement_id' => $requirement->id, 'student_enrollment_id' => $placement->student_enrollment_id, 'revision' => 2, 'status' => 'submitted']);
        $this->assertCount(1, $service->forPlacement($placement)['flags']);
    }
}
