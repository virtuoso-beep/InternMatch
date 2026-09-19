<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Competency;
use App\Models\HostEstablishment;
use App\Models\Placement;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HostOpportunityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function setupHost(): array
    {
        $host = HostEstablishment::factory()->create();
        $user = User::factory()->withRole(Role::Supervisor)->create();
        $user->hostEstablishments()->attach($host);
        $program = Program::factory()->create();
        $term = AcademicTerm::factory()->create();
        DB::table('host_program_capacity')->insert(['host_establishment_id' => $host->id, 'program_id' => $program->id, 'academic_term_id' => $term->id, 'capacity' => 3]);
        $this->actingAs($user);

        return [$host, $program, $term, $user];
    }

    public function test_supervisor_updates_only_assigned_host_profile_and_valid_coordinates(): void
    {
        [$host] = $this->setupHost();
        $other = HostEstablishment::factory()->create();
        $this->getJson('/api/v1/hosts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $host->id);
        $this->patchJson('/api/v1/hosts/'.$host->id, ['name' => 'Updated host', 'latitude' => 7.44, 'longitude' => 125.80])->assertOk();
        $this->assertDatabaseHas('host_establishments', ['id' => $host->id, 'name' => 'Updated host']);
        $this->patchJson('/api/v1/hosts/'.$other->id, ['name' => 'Forbidden'])->assertNotFound();
        $this->patchJson('/api/v1/hosts/'.$host->id, ['latitude' => 91, 'longitude' => 125])->assertUnprocessable();
        $this->patchJson('/api/v1/hosts/'.$host->id, ['is_active' => false])->assertForbidden();
    }

    public function test_opportunity_persists_program_capacities_and_competencies(): void
    {
        [$host, $program, $term] = $this->setupHost();
        $competency = Competency::factory()->create();
        $payload = ['host_establishment_id' => $host->id, 'academic_term_id' => $term->id,
            'title' => 'Systems support intern', 'description' => 'Assist the IT department', 'tasks' => 'Maintain systems and document issues',
            'status' => 'published', 'programs' => [['program_id' => $program->id, 'capacity' => 2]], 'competency_ids' => [$competency->id]];
        $response = $this->postJson('/api/v1/opportunities', $payload)->assertCreated();
        $id = $response->json('data.id');
        $this->assertDatabaseHas('opportunity_program', ['opportunity_id' => $id, 'program_id' => $program->id, 'capacity' => 2]);
        $this->assertDatabaseHas('opportunity_competency', ['opportunity_id' => $id, 'competency_id' => $competency->id]);
        $this->getJson('/api/v1/opportunities')->assertJsonPath('data.0.title', 'Systems support intern');
        $payload['programs'][0]['capacity'] = 4;
        $this->putJson('/api/v1/opportunities/'.$id, $payload)->assertUnprocessable();
        $this->assertDatabaseHas('opportunity_program', ['opportunity_id' => $id, 'program_id' => $program->id, 'capacity' => 2]);
        $payload['programs'][0]['capacity'] = 1;
        $payload['capacity'] = 100;
        $this->putJson('/api/v1/opportunities/'.$id, $payload)->assertUnprocessable();
    }

    public function test_coordinator_cannot_manage_capacity_of_another_program_at_a_shared_host(): void
    {
        [$host, $program, $term] = $this->setupHost();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($program);
        $other = Program::factory()->create();
        $this->actingAs($coordinator)->putJson('/api/v1/hosts/'.$host->id.'/capacities', ['capacities' => [
            ['program_id' => $other->id, 'academic_term_id' => $term->id, 'capacity' => 9],
        ]])->assertForbidden();
        $this->getJson('/api/v1/hosts/'.$host->id.'/capacities')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_host_capacity_cannot_fall_below_recorded_interns(): void
    {
        $placement = Placement::factory()->create();
        $user = User::factory()->withRole(Role::Supervisor)->create();
        $user->hostEstablishments()->attach($placement->host_establishment_id);
        $this->actingAs($user)->putJson('/api/v1/hosts/'.$placement->host_establishment_id.'/capacities', ['capacities' => [
            ['program_id' => $placement->studentEnrollment->programTerm->program_id, 'academic_term_id' => $placement->studentEnrollment->programTerm->academic_term_id, 'capacity' => 0],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('host_program_capacity', 0);
    }

    public function test_coordinator_can_create_host_only_for_assigned_programs(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $program = Program::factory()->create();
        $term = AcademicTerm::factory()->create();
        $coordinator->programs()->attach($program);
        $payload = ['code' => 'GOV-ICT', 'name' => 'Municipal ICT office', 'address' => 'City hall', 'city' => 'Tagum',
            'capacities' => [['program_id' => $program->id, 'academic_term_id' => $term->id, 'capacity' => 2]]];
        $this->actingAs($coordinator)->postJson('/api/v1/hosts', $payload)->assertCreated();
        $this->getJson('/api/v1/hosts')->assertJsonCount(1, 'data');
        $payload['code'] = 'OTHER';
        $payload['capacities'][0]['program_id'] = Program::factory()->create()->id;
        $this->postJson('/api/v1/hosts', $payload)->assertForbidden();
        $this->assertDatabaseCount('host_establishments', 1);
    }
}
