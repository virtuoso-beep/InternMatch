<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\Moa;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MoaWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registry_can_record_document_and_activate_explicit_program_coverage(): void
    {
        Storage::fake('local');
        $admin = User::factory()->withRole(Role::Admin)->create();
        $host = HostEstablishment::factory()->create();
        $program = Program::factory()->create();
        $payload = ['host_establishment_id' => $host->id, 'reference_number' => 'QA-MOA-001', 'status' => 'draft', 'effective_on' => '2026-09-01', 'expires_on' => '2027-03-01', 'is_institution_wide' => false, 'program_ids' => [$program->id]];
        $id = $this->actingAs($admin)->postJson('/api/v1/moas', $payload)->assertCreated()->json('data.id');
        $this->putJson('/api/v1/moas/'.$id, [...$payload, 'status' => 'active'])->assertUnprocessable();
        $this->postJson('/api/v1/moas/'.$id.'/document', ['file' => UploadedFile::fake()->create('agreement.pdf', 10, 'application/pdf')])->assertOk()->assertJsonMissingPath('data.document.path');
        $this->putJson('/api/v1/moas/'.$id, [...$payload, 'status' => 'active'])->assertOk();
        $this->get('/api/v1/moas/'.$id.'/document')->assertDownload('agreement.pdf');
        $this->assertTrue(Moa::findOrFail($id)->coversProgram($program->id));
        $this->postJson('/api/v1/moas/'.$id.'/document', ['file' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')])->assertConflict();
        $this->postJson('/api/v1/moas', [...$payload, 'reference_number' => 'QA-NO-COVERAGE', 'program_ids' => []])->assertUnprocessable();
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('audit_logs', 3);
    }

    public function test_coordinator_cannot_view_foreign_program_agreements_on_a_shared_host(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $program = Program::factory()->create();
        $coordinator->programs()->attach($program);
        $host = HostEstablishment::factory()->create();
        $own = Moa::factory()->create(['host_establishment_id' => $host->id]);
        $own->programs()->attach($program);
        $other = Moa::factory()->create(['host_establishment_id' => $host->id]);
        $other->programs()->attach(Program::factory()->create());
        $this->actingAs($coordinator)->getJson('/api/v1/moas')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->get('/api/v1/moas/'.$other->id.'/document')->assertNotFound();
        $this->putJson('/api/v1/moas/'.$other->id, [])->assertNotFound();
        $this->postJson('/api/v1/moas', ['host_establishment_id' => $host->id, 'reference_number' => 'QA-WIDE', 'status' => 'draft', 'is_institution_wide' => true, 'program_ids' => []])->assertUnprocessable(); // spec §5: programs must be listed
    }

    public function test_supervisor_can_register_drafts_but_cannot_activate_or_access_other_hosts(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $host = HostEstablishment::factory()->create();
        $supervisor->hostEstablishments()->attach($host);
        $program = Program::factory()->create();
        $payload = ['host_establishment_id' => $host->id, 'reference_number' => 'QA-DRAFT', 'status' => 'draft', 'is_institution_wide' => false, 'program_ids' => [$program->id]];
        $id = $this->actingAs($supervisor)->postJson('/api/v1/moas', $payload)->assertCreated()->json('data.id');
        $this->putJson('/api/v1/moas/'.$id, [...$payload, 'status' => 'active', 'effective_on' => '2026-09-01', 'expires_on' => '2027-03-01'])->assertForbidden();
        $other = Moa::factory()->create();
        $this->get('/api/v1/moas/'.$other->id.'/document')->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Student)->create())->getJson('/api/v1/moas')->assertForbidden();
    }

    public function test_spec_section_5_rejects_new_institution_wide_and_a_second_overlapping_active_moa(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $host = HostEstablishment::factory()->create();
        $program = Program::factory()->create();
        $this->actingAs($admin)->postJson('/api/v1/moas', ['host_establishment_id' => $host->id, 'reference_number' => 'QA-WIDE-ADMIN', 'status' => 'draft', 'is_institution_wide' => true, 'program_ids' => []])
            ->assertUnprocessable();
        Storage::fake('local');
        Moa::factory()->create(['host_establishment_id' => $host->id, 'reference_number' => 'QA-CURRENT', 'status' => 'active', 'effective_on' => '2026-01-01', 'expires_on' => '2026-12-31'])
            ->programs()->attach($program);
        foreach (['QA-OVERLAP' => ['2026-06-01', '2027-05-31', 422], 'QA-RENEWAL' => ['2027-01-01', '2027-12-31', 200]] as $reference => [$from, $to, $expected]) {
            $draft = ['host_establishment_id' => $host->id, 'reference_number' => $reference, 'status' => 'draft', 'is_institution_wide' => false, 'program_ids' => [$program->id]];
            $id = $this->postJson('/api/v1/moas', $draft)->assertCreated()->json('data.id');
            $this->post('/api/v1/moas/'.$id.'/document', ['file' => UploadedFile::fake()->create('moa.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertSuccessful();
            $response = $this->putJson('/api/v1/moas/'.$id, ['status' => 'active', 'effective_on' => $from, 'expires_on' => $to] + $draft)->assertStatus($expected);
            if ($expected === 422) {
                $this->assertStringContainsString('already has an active agreement (QA-CURRENT)', $response->json('message'));
            }
        }
        $this->assertSame(2, Moa::where('host_establishment_id', $host->id)->where('status', 'active')->count());
    }
}
