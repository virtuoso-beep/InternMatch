<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EventAttendance;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Models\ProgramTermRequirement;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\ProgramRequirementTemplates;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventRequirementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function context(): array
    {
        $this->seed(DatabaseSeeder::class);
        $term = ProgramTerm::factory()->create(['program_id' => Program::where('code', 'BSIT')->firstOrFail()->id]);
        ProgramRequirementTemplates::apply($term);
        $student = StudentEnrollment::factory()->create(['program_term_id' => $term->id]);
        $event = $term->requirements()->whereHas('requirementType', fn ($q) => $q->where('code', 'pdos'))->firstOrFail();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($term->program_id);
        return [$student, $event, $coordinator];
    }

    public function test_confirmed_catalog_is_program_scoped_idempotent_and_has_unscheduled_events(): void
    {
        [$student, $event] = $this->context();
        $this->seed(DatabaseSeeder::class);
        ProgramRequirementTemplates::apply($student->programTerm);
        $this->assertDatabaseCount('program_requirement_templates', 14);
        $this->assertSame(10, $student->programTerm->requirements()->whereHas('requirementType', fn ($q) => $q->where('kind', 'document'))->count());
        $this->assertSame(4, $student->programTerm->requirements()->whereHas('requirementType', fn ($q) => $q->where('kind', 'event'))->count());
        $this->assertNull($event->scheduled_on);
        $other = ProgramTerm::factory()->create();
        ProgramRequirementTemplates::apply($other);
        $this->assertSame(0, $other->requirements()->count());
        $event->update(['is_required' => false, 'scheduled_on' => '2026-10-02']);
        ProgramRequirementTemplates::apply($student->programTerm);
        $this->assertFalse($event->fresh()->is_required);
        $this->assertSame('2026-10-02', $event->fresh()->scheduled_on->toDateString());
    }

    public function test_student_attendance_requires_coordinator_confirmation_and_notifies_once(): void
    {
        [$student, $event, $coordinator] = $this->context();
        $base = '/api/v1/enrollments/'.$student->id;
        $attendance = $this->actingAs($student->student->user)->postJson($base.'/event-attendances', [
            'requirement_id' => $event->id, 'attended_on' => '2026-08-02', 'confirmed_by' => $coordinator->id,
        ])->assertOk()->assertJsonPath('data.confirmed_at', null)->json('data.id');
        $this->postJson($base.'/event-attendances/'.$attendance.'/confirm')->assertForbidden();
        $this->actingAs($coordinator)->postJson($base.'/event-attendances/'.$attendance.'/confirm')->assertOk();
        $this->postJson($base.'/event-attendances/'.$attendance.'/confirm')->assertOk();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('event_attendances', ['id' => $attendance, 'confirmed_by' => $coordinator->id]);
        $this->actingAs($student->student->user)->getJson($base.'/requirements')->assertOk()->assertJsonPath('attendances.0.attended_on', '2026-08-02');
        $this->postJson($base.'/event-attendances', ['requirement_id' => $event->id, 'attended_on' => '2026-08-03'])->assertConflict();
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'event.confirmed')->count());
    }

    public function test_attendance_cannot_cross_students_programs_or_document_types(): void
    {
        [$student, $event, $coordinator] = $this->context();
        $base = '/api/v1/enrollments/'.$student->id;
        $this->actingAs(User::factory()->withRole(Role::Student)->create())->postJson($base.'/event-attendances', ['requirement_id' => $event->id, 'attended_on' => '2026-08-02'])->assertNotFound();
        $this->actingAs($student->student->user);
        $this->postJson($base.'/event-attendances', ['requirement_id' => $event->id, 'attended_on' => now()->addDay()->toDateString()])->assertUnprocessable();
        $document = $student->programTerm->requirements()->whereHas('requirementType', fn ($q) => $q->where('kind', 'document'))->firstOrFail();
        $this->postJson($base.'/event-attendances', ['requirement_id' => $document->id, 'attended_on' => '2026-08-02'])->assertUnprocessable();
        $other = ProgramTermRequirement::factory()->create();
        $this->postJson($base.'/event-attendances', ['requirement_id' => $other->id, 'attended_on' => '2026-08-02'])->assertNotFound();
        Storage::fake('local');
        $this->postJson($base.'/requirements', ['requirement_id' => $event->id, 'file' => UploadedFile::fake()->create('event.pdf', 1, 'application/pdf')])->assertUnprocessable();
        $this->assertDatabaseCount('documents', 0);
        $id = $this->postJson($base.'/event-attendances', ['requirement_id' => $event->id, 'attended_on' => '2026-08-02'])->assertOk()->json('data.id');
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create())->postJson($base.'/event-attendances/'.$id.'/confirm')->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson($base.'/event-attendances/'.$id.'/confirm')->assertForbidden();
        $student->update(['status' => 'withdrawn']);
        $this->actingAs($coordinator)->postJson($base.'/event-attendances/'.$id.'/confirm')->assertConflict();
        $this->assertNull(EventAttendance::findOrFail($id)->confirmed_at);
    }
}
