<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\AcademicTerm;
use App\Models\Placement;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentStudentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function coordinator(StudentEnrollment $record): User
    {
        $user = User::factory()->withRole(Role::Coordinator)->create();
        $user->programs()->attach($record->programTerm->program_id);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('manage'));

        return $user;
    }

    public function test_native_list_search_and_direct_urls_enforce_program_scope(): void
    {
        $own = StudentEnrollment::factory()->create();
        $outside = StudentEnrollment::factory()->create();
        // Same identity in another program must not expose that enrollment either.
        $otherTerm = StudentEnrollment::factory()->create(['student_id' => $own->student_id]);
        $coordinator = $this->coordinator($own);
        Livewire::test(ListStudents::class)->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$outside, $otherTerm])->assertActionHidden('create');
        Livewire::test(ListStudents::class)->searchTable($outside->student->student_number)
            ->assertCanNotSeeTableRecords([$outside]);
        $this->get('/manage/students/'.$outside->id.'/edit')->assertNotFound();
        $this->get('/manage/students/create')->assertForbidden();
        $this->patchJson('/api/v1/enrollments/'.$outside->id, ['year_level' => 3, 'enrolled_on' => '2026-08-01'])->assertNotFound();
        $coordinator->programs()->detach();
        $this->patchJson('/api/v1/enrollments/'.$own->id, ['year_level' => 3, 'enrolled_on' => '2026-08-01'])->assertNotFound();
    }

    public function test_coordinator_edit_persists_and_audits_without_mutating_protected_fields(): void
    {
        $record = StudentEnrollment::factory()->create();
        $coordinator = $this->coordinator($record);
        Livewire::test(EditStudent::class, ['record' => $record->id])
            ->fillForm(['year_level' => 4, 'enrolled_on' => '2026-08-01', 'target_completion_on' => '2026-12-31'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('student_enrollments', ['id' => $record->id, 'year_level' => 4, 'target_completion_on' => '2026-12-31']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $coordinator->id, 'action' => 'student.enrollment_updated', 'program_id' => $record->programTerm->program_id]);
        $original = $record->only(['student_id', 'program_term_id', 'required_minutes']);
        $this->patchJson('/api/v1/enrollments/'.$record->id, [
            'year_level' => 3, 'enrolled_on' => '2026-08-01', 'status' => 'completed',
            'student_id' => 999999, 'program_term_id' => 999999, 'required_minutes' => 1,
        ])->assertOk()->assertJsonPath('data.status', 'enrolled');
        $this->assertSame($original, $record->fresh()->only(array_keys($original)));
        Livewire::test(EditStudent::class, ['record' => $record->id])->assertFormSet(['year_level' => 3]);
    }

    public function test_invalid_dates_closed_enrollments_and_unauthorized_roles_cannot_edit(): void
    {
        $record = StudentEnrollment::factory()->create();
        $this->coordinator($record);
        Livewire::test(EditStudent::class, ['record' => $record->id])
            ->fillForm(['year_level' => 0, 'enrolled_on' => '2026-08-01', 'target_completion_on' => '2026-07-01'])
            ->call('save')->assertHasFormErrors(['year_level', 'target_completion_on']);
        $record->update(['status' => 'completed']);
        $payload = ['year_level' => 4, 'enrolled_on' => '2026-08-01'];
        $this->patchJson('/api/v1/enrollments/'.$record->id, $payload)->assertUnprocessable();
        $this->actingAs($record->student->user)->patchJson('/api/v1/enrollments/'.$record->id, $payload)->assertForbidden();
        $dean = User::factory()->withRole(Role::Dean)->create();
        $dean->programs()->attach($record->programTerm->program_id);
        $this->actingAs($dean)->patchJson('/api/v1/enrollments/'.$record->id, $payload)->assertForbidden();
        $this->get('/manage/students')->assertForbidden();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_native_admin_creation_uses_confirmed_hours_and_duplicate_validation(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $student = User::factory()->withRole(Role::Student)->create();
        $program = Program::factory()->create(['required_ojt_hours' => null]);
        $term = AcademicTerm::factory()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        $payload = ['student_email' => $student->email, 'student_number' => 'NATIVE-QA-001',
            'program_id' => $program->id, 'academic_term_id' => $term->id,
            'year_level' => 4, 'enrolled_on' => '2026-08-01', 'target_completion_on' => '2026-12-31'];
        Livewire::test(CreateStudent::class)->fillForm($payload)->call('create')->assertHasFormErrors(['program_id']);
        $this->assertDatabaseCount('student_enrollments', 0);
        $program->update(['required_ojt_hours' => 100]); // Isolated fixture only.
        Livewire::test(CreateStudent::class)->fillForm($payload)->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('student_enrollments', ['required_minutes' => 6000, 'year_level' => 4]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.enrolled', 'program_id' => $program->id]);
        Livewire::test(CreateStudent::class)->fillForm($payload)->call('create')->assertHasFormErrors(['program_id']);
        $this->assertDatabaseCount('student_enrollments', 1);
    }

    public function test_withdrawal_preserves_history_requires_reason_and_rejects_placements(): void
    {
        $record = StudentEnrollment::factory()->create();
        $this->coordinator($record);
        $url = '/api/v1/enrollments/'.$record->id.'/withdraw';
        $this->postJson($url, [])->assertUnprocessable();
        $this->postJson($url, ['reason' => 'Synthetic QA withdrawal'])->assertOk()->assertJsonPath('data.status', 'withdrawn');
        $this->assertDatabaseHas('student_enrollments', ['id' => $record->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.withdrawn', 'subject_id' => $record->id]);
        $this->postJson($url, ['reason' => 'Repeated'])->assertUnprocessable();
        $record->refresh()->update(['status' => 'enrolled']);
        Placement::factory()->create(['student_enrollment_id' => $record->id]);
        $this->postJson($url, ['reason' => 'Has placement'])->assertUnprocessable();
        $this->assertDatabaseHas('student_enrollments', ['id' => $record->id, 'status' => 'enrolled']);
    }
}
