<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Models\Program;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_panel_rejects_operational_roles_and_inactive_accounts(): void
    {
        $this->get('/manage')->assertRedirect('/manage/login');
        foreach ([Role::Student, Role::Supervisor, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->get('/manage')->assertForbidden();
        }
        foreach ([Role::Admin, Role::Coordinator] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->get('/manage')->assertOk();
            $user->status = AccountStatus::Disabled;
            $user->save();
            $this->get('/manage')->assertForbidden();
        }
    }

    public function test_coordinator_sees_only_assigned_programs_and_cannot_edit_hours(): void
    {
        $own = Program::factory()->create();
        $outside = Program::factory()->create();
        $user = User::factory()->withRole(Role::Coordinator)->create();
        $user->programs()->attach($own);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        Livewire::test(ListPrograms::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$outside]);
        $this->get('/manage/programs/'.$own->id.'/edit')->assertForbidden();
        $this->get('/manage/programs/'.$outside->id.'/edit')->assertNotFound();
    }

    public function test_admin_program_edit_uses_shared_validation_and_audit(): void
    {
        $program = Program::factory()->create(['required_ojt_hours' => null]);
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        Livewire::test(EditProgram::class, ['record' => $program->id])
            ->fillForm(['required_ojt_hours' => -1])->call('save')->assertHasFormErrors(['required_ojt_hours']);
        Livewire::test(EditProgram::class, ['record' => $program->id])
            ->fillForm(['required_ojt_hours' => 100, 'internship_term' => 'Synthetic QA term', 'is_active' => true])
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'required_ojt_hours' => 100]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'program_id' => $program->id, 'action' => 'program.updated']);
    }
}
