<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicProvisioningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_provision_accounts_without_exposing_credentials_and_assign_supervisor_hosts(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $payload = ['name' => 'QA Supervisor', 'email' => 'qa-supervisor@example.test', 'password' => 'Testing-only-password-42!', 'role' => 'supervisor', 'status' => 'active'];
        $id = $this->actingAs($admin)->postJson('/api/v1/users', $payload)->assertCreated()->assertJsonMissingPath('data.password')->json('data.id');
        $this->assertTrue(Hash::check($payload['password'], User::findOrFail($id)->password));
        $this->assertStringNotContainsString($payload['password'], DB::table('audit_logs')->first()->changes);
        $host = HostEstablishment::factory()->create();
        $this->putJson('/api/v1/users/'.$id.'/hosts', ['host_ids' => [$host->id]])->assertNoContent();
        $this->assertDatabaseHas('host_establishment_user', ['user_id' => $id, 'host_establishment_id' => $host->id]);
        $this->actingAs(User::findOrFail($id))->getJson('/api/v1/hosts')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [...$payload, 'email' => 'other@example.test'])->assertForbidden();
        $this->putJson('/api/v1/users/'.$id.'/hosts', ['host_ids' => []])->assertForbidden();
    }

    public function test_enrollment_requires_confirmed_program_hours_and_preserves_enrollment_snapshot(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $student = User::factory()->withRole(Role::Student)->create();
        $program = Program::factory()->create(['required_ojt_hours' => null]);
        $term = AcademicTerm::factory()->create();
        $payload = ['student_email' => $student->email, 'student_number' => 'QA-0001', 'program_id' => $program->id, 'academic_term_id' => $term->id, 'year_level' => 4, 'enrolled_on' => '2026-09-19', 'target_completion_on' => '2027-02-01'];
        $this->actingAs($admin)->postJson('/api/v1/enrollments', $payload)->assertUnprocessable()->assertJsonValidationErrors('program_id');
        $this->assertDatabaseCount('student_enrollments', 0);
        // This confirmed value is a test fixture, never an approved live program requirement.
        $program->update(['required_ojt_hours' => 420]);
        $id = $this->postJson('/api/v1/enrollments', $payload)->assertCreated()->assertJsonPath('data.required_minutes', 25200)->json('data.id');
        $this->postJson('/api/v1/enrollments', $payload)->assertUnprocessable();
        $program->update(['required_ojt_hours' => 500]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $id, 'required_minutes' => 25200]);
        $this->actingAs($student)->postJson('/api/v1/enrollments', $payload)->assertForbidden();
        $this->getJson('/api/v1/enrollments')->assertJsonCount(1, 'data');
    }

    public function test_term_dates_and_student_identity_are_validated(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->postJson('/api/v1/academic-terms', ['code' => 'QA2026', 'academic_year' => '2026-2027', 'name' => 'QA term', 'starts_on' => '2026-09-01', 'ends_on' => '2026-08-01'])->assertUnprocessable();
        $term = $this->postJson('/api/v1/academic-terms', ['code' => 'QA2026', 'academic_year' => '2026-2027', 'name' => 'QA term', 'starts_on' => '2026-09-01', 'ends_on' => '2027-02-01'])->assertCreated()->json('data.id');
        $program = Program::factory()->create(['required_ojt_hours' => 100]);
        $payload = ['user_id' => $admin->id, 'student_number' => 'QA-0002', 'program_id' => $program->id, 'academic_term_id' => $term, 'year_level' => 4, 'enrolled_on' => '2026-09-19'];
        $this->postJson('/api/v1/enrollments', $payload)->assertUnprocessable();
        $this->postJson('/api/v1/users', ['name' => 'QA User', 'email' => 'qa@example.test', 'password' => 'short', 'role' => 'student', 'status' => 'active'])->assertUnprocessable()->assertJsonValidationErrors('password');
    }
}
