<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Models\Competency;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentOpportunityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function setupRecords(): array
    {
        $placement = Placement::factory()->create();
        $host = $placement->hostEstablishment;
        $program = $placement->studentEnrollment->programTerm->program;
        $term = $placement->studentEnrollment->programTerm->academic_term_id;
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($program);
        DB::table('host_program_capacity')->insert(['host_establishment_id' => $host->id, 'program_id' => $program->id, 'academic_term_id' => $term, 'capacity' => 3]);
        $this->actingAs($actor);
        Filament::setCurrentPanel(Filament::getPanel('manage'));

        return [$placement, $host, $program, $term, $actor];
    }

    public function test_native_creation_edit_close_and_competencies_persist_with_audit(): void
    {
        [$placement, $host, $program, $term, $actor] = $this->setupRecords();
        $competency = Competency::factory()->create();
        $payload = ['host_establishment_id' => $host->id, 'academic_term_id' => $term, 'title' => 'Native QA opportunity',
            'description' => 'Synthetic QA description', 'tasks' => 'Document QA workflows', 'status' => 'published',
            'programs' => [['program_id' => $program->id, 'capacity' => 2]], 'competency_ids' => [$competency->id]];
        Livewire::test(CreateOpportunity::class)->fillForm($payload)->call('create')->assertHasNoFormErrors();
        $record = Opportunity::where('title', 'Native QA opportunity')->firstOrFail();
        $this->assertDatabaseHas('opportunity_competency', ['opportunity_id' => $record->id, 'competency_id' => $competency->id]);
        $this->assertDatabaseHas('opportunity_program', ['opportunity_id' => $record->id, 'program_id' => $program->id, 'capacity' => 2]);
        Livewire::test(EditOpportunity::class, ['record' => $record->id])->fillForm(['title' => 'Native QA updated', 'status' => 'closed'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('opportunities', ['id' => $record->id, 'title' => 'Native QA updated', 'status' => 'closed']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'action' => 'opportunity.updated', 'subject_id' => $record->id]);
    }

    public function test_capacity_limits_and_existing_placements_reject_invalid_changes(): void
    {
        [$placement, $host, $program, $term] = $this->setupRecords();
        $record = $placement->opportunity;
        $record->update(['host_establishment_id' => $host->id, 'academic_term_id' => $term]);
        $record->programs()->sync([$program->id => ['capacity' => 2]]);
        foreach ([0, 4] as $capacity) {
            Livewire::test(EditOpportunity::class, ['record' => $record->id])->fillForm([
                'title' => 'Must not save', 'tasks' => 'Synthetic placement capacity check', 'programs' => [['program_id' => $program->id, 'capacity' => $capacity]],
            ])->call('save')->assertHasFormErrors(['programs']);
        }
        $this->assertDatabaseMissing('opportunities', ['id' => $record->id, 'title' => 'Must not save']);
        $this->assertDatabaseHas('opportunity_program', ['opportunity_id' => $record->id, 'capacity' => 2]);
    }

    public function test_scopes_and_shared_program_edit_restrictions_apply_to_native_and_api(): void
    {
        [$placement, $host, $program, $term] = $this->setupRecords();
        $own = Opportunity::factory()->create(['host_establishment_id' => $host->id, 'academic_term_id' => $term]);
        $own->programs()->attach($program, ['capacity' => 2]);
        $outside = Opportunity::factory()->create();
        Livewire::test(ListOpportunities::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$outside]);
        $this->get('/manage/opportunities/'.$outside->id.'/edit')->assertNotFound();
        $other = Program::factory()->create();
        $own->programs()->attach($other, ['capacity' => 1]);
        $this->get('/manage/opportunities/'.$own->id.'/edit')->assertForbidden();
        $this->putJson('/api/v1/opportunities/'.$own->id, ['host_establishment_id' => $host->id, 'academic_term_id' => $term,
            'title' => 'Attempt to remove outside program', 'description' => 'QA', 'tasks' => 'QA', 'status' => 'draft',
            'programs' => [['program_id' => $program->id, 'capacity' => 2]], 'competency_ids' => []])->assertForbidden();
        $this->assertDatabaseHas('opportunity_program', ['opportunity_id' => $own->id, 'program_id' => $other->id]);
        $student = User::factory()->withRole(Role::Student)->create();
        $this->actingAs($student)->get('/manage/opportunities')->assertForbidden();
        $this->postJson('/api/v1/opportunities', [])->assertForbidden();
    }
}
