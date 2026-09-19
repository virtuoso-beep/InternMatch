<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequirementWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function context(): array
    {
        Storage::fake('local');
        $enrollment = StudentEnrollment::factory()->create();
        $requirement = ProgramTermRequirement::factory()->create(['program_term_id' => $enrollment->program_term_id]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($enrollment->programTerm->program_id);

        return [$enrollment, $requirement, $coordinator];
    }

    private function upload(StudentEnrollment $enrollment, ProgramTermRequirement $requirement): int
    {
        return $this->actingAs($enrollment->student->user)->postJson('/api/v1/enrollments/'.$enrollment->id.'/requirements', [
            'requirement_id' => $requirement->id, 'file' => UploadedFile::fake()->create('insurance.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonMissingPath('data.document.path')->assertJsonMissingPath('data.document.disk')->json('data.id');
    }

    public function test_document_upload_revision_review_notification_and_download_are_persisted(): void
    {
        [$enrollment, $requirement, $coordinator] = $this->context();
        $id = $this->upload($enrollment, $requirement);
        $submission = RequirementSubmission::findOrFail($id);
        Storage::disk('local')->assertExists($submission->document->path);
        $this->get('/api/v1/submissions/'.$id.'/document')->assertDownload('insurance.pdf');
        $this->getJson('/api/v1/enrollments/'.$enrollment->id.'/requirements')->assertJsonPath('submissions.0.revision', 1);
        $this->actingAs($coordinator)->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'under_review'])->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'rejected'])->assertUnprocessable();
        $this->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'rejected', 'comments' => 'Upload the signed page.'])->assertOk()->assertJsonPath('data.reviews.0.comments', 'Upload the signed page.');
        $this->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'approved'])->assertForbidden();
        $second = $this->upload($enrollment, $requirement);
        $this->assertDatabaseHas('requirement_submissions', ['id' => $second, 'revision' => 2, 'status' => 'submitted']);
        $this->actingAs($coordinator)->postJson('/api/v1/submissions/'.$second.'/review', ['status' => 'approved'])->assertOk();
        $notifications = $this->actingAs($enrollment->student->user)->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('unread_count', 3)->json('data');
        $this->patchJson('/api/v1/notifications/'.$notifications[0]['id'].'/read')->assertNoContent();
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread_count', 2);
        $this->assertDatabaseCount('requirement_reviews', 2);
        $this->assertDatabaseCount('audit_logs', 5);
    }

    public function test_document_and_notification_access_cannot_cross_ownership_or_program(): void
    {
        [$enrollment, $requirement, $coordinator] = $this->context();
        $id = $this->upload($enrollment, $requirement);
        $this->actingAs($coordinator)->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'under_review'])->assertOk();
        $notification = $enrollment->student->user->notifications()->firstOrFail();
        foreach ([Role::Student, Role::Coordinator, Role::Dean, Role::Supervisor] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->get('/api/v1/submissions/'.$id.'/document')->assertForbidden();
            $this->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'approved'])->assertForbidden();
            $this->getJson('/api/v1/notifications')->assertJsonCount(0, 'data');
            $this->patchJson('/api/v1/notifications/'.$notification->id.'/read')->assertNotFound();
        }
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->get('/api/v1/submissions/'.$id.'/document')->assertDownload();
        $this->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'approved'])->assertForbidden();
    }

    public function test_only_latest_revision_can_be_reviewed_and_foreign_requirement_cannot_be_uploaded(): void
    {
        [$enrollment, $requirement, $coordinator] = $this->context();
        $id = $this->upload($enrollment, $requirement);
        $this->upload($enrollment, $requirement);
        $this->actingAs($coordinator)->postJson('/api/v1/submissions/'.$id.'/review', ['status' => 'approved'])->assertConflict();
        $other = ProgramTermRequirement::factory()->create();
        $this->actingAs($enrollment->student->user)->postJson('/api/v1/enrollments/'.$enrollment->id.'/requirements', ['requirement_id' => $other->id, 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertNotFound();
        $this->postJson('/api/v1/enrollments/'.$enrollment->id.'/requirements', ['requirement_id' => $requirement->id, 'file' => UploadedFile::fake()->create('a.exe', 10, 'application/octet-stream')])->assertUnprocessable();
        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseCount('requirement_reviews', 0);
    }

    public function test_requirement_configuration_is_program_scoped_and_updates_in_place(): void
    {
        [$enrollment, $requirement, $coordinator] = $this->context();
        $payload = ['requirement_type_id' => $requirement->requirement_type_id, 'is_required' => true, 'required_before_deployment' => true, 'due_at' => '2026-10-01 09:00:00'];
        $this->actingAs($coordinator)->postJson('/api/v1/program-terms/'.$enrollment->program_term_id.'/requirements', $payload)->assertOk();
        $this->assertDatabaseCount('program_term_requirements', 1);
        $other = StudentEnrollment::factory()->create();
        $this->getJson('/api/v1/requirement-configuration')->assertOk()->assertJsonCount(1, 'program_terms')->assertJsonPath('program_terms.0.id', $enrollment->program_term_id);
        $this->postJson('/api/v1/program-terms/'.$other->program_term_id.'/requirements', $payload)->assertForbidden();
        $this->actingAs($enrollment->student->user)->postJson('/api/v1/program-terms/'.$enrollment->program_term_id.'/requirements', $payload)->assertForbidden();
    }
}
