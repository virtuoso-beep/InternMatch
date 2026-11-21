<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\Placement;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Coordinator certifies a deployment's hours, then ends it; the chat becomes read-only for the supervisor. */
class PlacementCompletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const LOG = ['time_in' => '2026-09-18T08:00:00+08:00', 'time_out' => '2026-09-18T17:00:00+08:00', 'break_minutes' => 60];

    /** @return array{0: Placement, 1: User, 2: User, 3: User} placement, supervisor, student, coordinator */
    private function deployment(int $requiredHours = 8): array
    {
        $this->travelTo(now()->setDate(2026, 9, 19)->setTime(12, 0));
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => '2026-09-01', 'ends_on' => '2026-09-30']);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $placement->studentEnrollment->programTerm->program->update(['required_ojt_hours' => $requiredHours]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($placement->studentEnrollment->programTerm->program_id);

        return [$placement, $supervisor, $placement->studentEnrollment->student->user, $coordinator];
    }

    public function test_certify_then_end_deployment_completes_the_placement_and_closes_chat_for_the_supervisor(): void
    {
        [$placement, $supervisor, $student, $coordinator] = $this->deployment();
        $id = $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", self::LOG)->assertCreated()->json('data.id');

        // A pending log blocks certification; ending requires certification first.
        $this->actingAs($coordinator)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertConflict();
        $this->postJson("/api/v1/placements/{$placement->id}/end")->assertConflict();

        $this->actingAs($supervisor)->postJson("/api/v1/time-logs/{$id}/verify", ['status' => 'verified'])->assertOk();
        $this->actingAs($coordinator)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertOk()->assertJsonPath('data.certified_minutes', 480);
        $this->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertConflict();

        // Certified hours are frozen: no new logs.
        $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", [...self::LOG, 'time_in' => '2026-09-17T08:00:00+08:00', 'time_out' => '2026-09-17T17:00:00+08:00'])->assertConflict();

        $conversation = Conversation::withoutGlobalScope('participant')->where('placement_id', $placement->id)->firstOrFail();
        $this->actingAs($supervisor)->postJson("/api/v1/chat/conversations/{$conversation->id}/messages", ['body' => 'Before the end', 'client_id' => (string) Str::uuid()])->assertOk();

        $this->actingAs($coordinator)->postJson("/api/v1/placements/{$placement->id}/end")->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('placements', ['id' => $placement->id, 'status' => 'completed', 'completed_by' => $coordinator->id]);
        $this->assertNotNull($placement->fresh()->completed_at);
        $this->postJson("/api/v1/placements/{$placement->id}/end")->assertConflict();

        $this->actingAs($supervisor)->postJson("/api/v1/chat/conversations/{$conversation->id}/messages", ['body' => 'After the end', 'client_id' => (string) Str::uuid()])->assertForbidden();
        $this->assertDatabaseMissing('chat_messages', ['body' => 'After the end']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'placement.completed', 'subject_id' => $placement->id]);
    }

    public function test_start_deployment_makes_an_approved_placement_active_from_its_start_date(): void
    {
        [$placement, $supervisor, $student, $coordinator] = $this->deployment();
        $placement->update(['status' => 'approved', 'starts_on' => '2026-09-20']);
        foreach ([$student, $supervisor, User::factory()->withRole(Role::Dean)->create()] as $user) {
            $this->actingAs($user)->postJson("/api/v1/placements/{$placement->id}/start")->assertForbidden();
        }
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create())->postJson("/api/v1/placements/{$placement->id}/start")->assertNotFound();
        $this->actingAs($coordinator)->postJson("/api/v1/placements/{$placement->id}/start")->assertUnprocessable();
        $this->travelTo(now()->setDate(2026, 9, 21)->setTime(9, 0));
        $this->postJson("/api/v1/placements/{$placement->id}/start")->assertOk()->assertJsonPath('data.status', 'active');
        $this->postJson("/api/v1/placements/{$placement->id}/start")->assertConflict();
        $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", ['time_in' => '2026-09-21T08:00:00+08:00', 'time_out' => '2026-09-21T08:59:00+08:00', 'break_minutes' => 0])->assertCreated();
    }

    public function test_deployment_cannot_end_below_the_programs_required_hours(): void
    {
        [$placement, $supervisor, $student, $coordinator] = $this->deployment(400);
        $id = $this->actingAs($student)->postJson("/api/v1/placements/{$placement->id}/time-logs", self::LOG)->assertCreated()->json('data.id');
        $this->actingAs($supervisor)->postJson("/api/v1/time-logs/{$id}/verify", ['status' => 'verified'])->assertOk();
        $this->actingAs($coordinator)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertOk();
        $this->postJson("/api/v1/placements/{$placement->id}/end")->assertUnprocessable()->assertJsonPath('message', 'Certified hours (8) are below the required 400 hours.');
        $this->assertSame('active', $placement->fresh()->status->value);
    }

    public function test_only_the_programs_coordinator_or_an_admin_can_certify_or_end(): void
    {
        [$placement, $supervisor, $student] = $this->deployment();
        foreach ([$student, $supervisor, User::factory()->withRole(Role::Dean)->create()] as $user) {
            $this->actingAs($user)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertForbidden();
            $this->postJson("/api/v1/placements/{$placement->id}/end")->assertForbidden();
        }
        $outsider = User::factory()->withRole(Role::Coordinator)->create();
        $this->actingAs($outsider)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertNotFound();
        $this->postJson("/api/v1/placements/{$placement->id}/end")->assertNotFound();
        // A coordinator still listed on another program cannot reach this placement either.
        $otherProgram = User::factory()->withRole(Role::Coordinator)->create();
        $otherProgram->programs()->attach(Program::factory()->create()->id);
        $this->actingAs($otherProgram)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertNotFound();
        $this->postJson("/api/v1/placements/{$placement->id}/end")->assertNotFound();
        $this->assertNull($placement->fresh()->hours_certified_at);

        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->postJson("/api/v1/placements/{$placement->id}/certify-hours")->assertOk()->assertJsonPath('data.certified_minutes', 0);
    }
}
