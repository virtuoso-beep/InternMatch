<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Hosts\Pages\CreateHost;
use App\Filament\Resources\Hosts\Pages\EditHost;
use App\Filament\Resources\Hosts\Pages\ListHosts;
use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Placement;
use App\Models\Program;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentHostTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function coordinator(Program $program): User
    {
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($program);
        $this->actingAs($actor);
        Filament::setCurrentPanel(Filament::getPanel('manage'));

        return $actor;
    }

    public function test_native_creation_is_scoped_validated_and_audited(): void
    {
        $program = Program::factory()->create();
        $term = AcademicTerm::factory()->create();
        $actor = $this->coordinator($program);
        $payload = ['code' => 'NATIVE-HOST-QA', 'name' => 'Native QA Host', 'address' => 'QA address', 'city' => 'QA city',
            'is_active' => true, 'latitude' => 7.44, 'longitude' => 125.8,
            'capacities' => [['program_id' => $program->id, 'academic_term_id' => $term->id, 'capacity' => 3]]];
        Livewire::test(CreateHost::class)->fillForm($payload)->call('create')->assertHasNoFormErrors();
        $host = HostEstablishment::where('code', 'NATIVE-HOST-QA')->firstOrFail();
        $this->assertDatabaseHas('host_program_capacity', ['host_establishment_id' => $host->id, 'program_id' => $program->id, 'capacity' => 3]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'action' => 'host.created']);
        Livewire::test(CreateHost::class)->fillForm($payload)->call('create')->assertHasFormErrors(['code']);
        $this->assertDatabaseCount('host_establishments', 1);
        $outside = HostEstablishment::factory()->create();
        Livewire::test(ListHosts::class)->assertCanSeeTableRecords([$host])->assertCanNotSeeTableRecords([$outside]);
        $this->get('/manage/hosts/'.$outside->id.'/edit')->assertNotFound();
        $payload['code'] = 'FORGED-HOST';
        $payload['capacities'][0]['program_id'] = Program::factory()->create()->id;
        Livewire::test(CreateHost::class)->fillForm($payload)->call('create')->assertHasFormErrors();
        $this->assertDatabaseMissing('host_establishments', ['code' => 'FORGED-HOST']);
    }

    public function test_shared_host_edit_preserves_other_program_capacity_and_can_deactivate(): void
    {
        $host = HostEstablishment::factory()->create();
        $program = Program::factory()->create();
        $other = Program::factory()->create();
        $term = AcademicTerm::factory()->create();
        foreach ([$program, $other] as $p) {
            DB::table('host_program_capacity')->insert(['host_establishment_id' => $host->id, 'program_id' => $p->id, 'academic_term_id' => $term->id, 'capacity' => 4]);
        }
        $this->coordinator($program);
        $page = Livewire::test(EditHost::class, ['record' => $host->id]);
        $this->assertCount(1, $page->get('data.capacities'));
        $page->fillForm(['name' => 'Updated native host', 'is_active' => false,
            'capacities' => [['program_id' => $program->id, 'academic_term_id' => $term->id, 'capacity' => 2]]])
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('host_establishments', ['id' => $host->id, 'name' => 'Updated native host', 'is_active' => false]);
        $this->assertDatabaseHas('host_program_capacity', ['host_establishment_id' => $host->id, 'program_id' => $other->id, 'capacity' => 4]);
        $this->assertDatabaseHas('host_program_capacity', ['host_establishment_id' => $host->id, 'program_id' => $program->id, 'capacity' => 2]);
    }

    public function test_invalid_capacity_rolls_back_profile_changes_and_unauthorized_roles_are_denied(): void
    {
        $placement = Placement::factory()->create();
        $host = $placement->hostEstablishment;
        $program = $placement->studentEnrollment->programTerm->program;
        $term = $placement->studentEnrollment->programTerm->academic_term_id;
        DB::table('host_program_capacity')->insert(['host_establishment_id' => $host->id, 'program_id' => $program->id, 'academic_term_id' => $term, 'capacity' => 2]);
        $this->coordinator($program);
        Livewire::test(EditHost::class, ['record' => $host->id])->fillForm(['name' => 'Must roll back',
            'capacities' => [['program_id' => $program->id, 'academic_term_id' => $term, 'capacity' => 0]]])
            ->call('save')->assertHasFormErrors(['capacities']);
        $this->assertSame($host->name, $host->fresh()->name);
        $this->assertDatabaseCount('audit_logs', 0);
        foreach ([Role::Student, Role::Dean, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->get('/manage/hosts')->assertForbidden();
        }
    }
}
