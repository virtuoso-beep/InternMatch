<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/** A returned time log or journal shows the reason and can be corrected and resubmitted by the student. */
class ReturnedLogResubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function deployment(): array
    {
        $this->travelTo(now()->setDate(2026, 9, 19)->setTime(12, 0));
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => '2026-09-01', 'ends_on' => '2026-09-30']);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($placement->studentEnrollment->programTerm->program_id);

        return [$placement, $supervisor, $placement->studentEnrollment->student->user, $coordinator];
    }

    public function test_returned_time_log_can_be_corrected_and_resubmitted(): void
    {
        [$placement, $supervisor, $student] = $this->deployment();
        $log = ['time_in' => '2026-09-18T08:00:00+08:00', 'time_out' => '2026-09-18T17:00:00+08:00', 'break_minutes' => 60];
        $id = $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", $log)->assertCreated()->json('data.id');

        // Returning requires a reason.
        $this->actingAs($supervisor)->postJson("/api/v1/time-logs/{$id}/verify", ['status' => 'flagged'])->assertUnprocessable();
        $this->postJson("/api/v1/time-logs/{$id}/verify", ['status' => 'flagged', 'notes' => 'Time out does not match the gate log.'])->assertOk()->assertJsonPath('data.status', 'flagged');
        $this->actingAs($student)->getJson("/api/v1/placements/{$placement->id}/monitoring")->assertJsonPath('time_logs.0.notes', 'Time out does not match the gate log.');

        $this->postJson("/api/v1/placements/{$placement->id}/time-logs", [...$log, 'id' => $id, 'time_out' => '2026-09-18T16:00:00+08:00', 'notes' => 'Corrected time out.'])->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseCount('time_logs', 1);
        $this->actingAs($supervisor)->postJson("/api/v1/time-logs/{$id}/verify", ['status' => 'verified'])->assertOk()->assertJsonPath('data.credited_minutes', 420);

        // A certified log stays locked.
        $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", [...$log, 'id' => $id])->assertConflict();
    }

    public function test_returned_journal_keeps_the_reason_and_can_be_resubmitted(): void
    {
        [$placement, , $student, $coordinator] = $this->deployment();
        $journal = ['week_starts_on' => '2026-09-14', 'content' => 'Prepared the weekly inventory report.', 'status' => 'submitted'];
        $id = $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/journals", $journal)->assertOk()->json('data.id');

        $this->actingAs($coordinator)->postJson("/api/v1/journals/{$id}/review", ['status' => 'rejected'])->assertUnprocessable();
        $this->postJson("/api/v1/journals/{$id}/review", ['status' => 'rejected', 'comments' => 'Describe the tasks you did each day.'])->assertOk()
            ->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.review_comments', 'Describe the tasks you did each day.');
        $this->actingAs($student)->getJson("/api/v1/placements/{$placement->id}/monitoring")->assertJsonPath('journals.0.review_comments', 'Describe the tasks you did each day.');

        $this->postJson("/api/v1/placements/{$placement->id}/journals", [...$journal, 'content' => 'Monday: inventory. Tuesday: audit support.'])->assertOk()->assertJsonPath('data.status', 'submitted');
        $this->assertDatabaseCount('journal_entries', 1);
        $this->actingAs($coordinator)->postJson("/api/v1/journals/{$id}/review", ['status' => 'approved'])->assertOk()->assertJsonPath('data.review_comments', null);
    }
}
