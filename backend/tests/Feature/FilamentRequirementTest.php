<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Requirements\Pages\ListRequirements;
use App\Models\RequirementSubmission;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentRequirementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_coordinator_review_is_scoped_validated_and_persisted(): void
    {
        $own = RequirementSubmission::factory()->create();
        $outside = RequirementSubmission::factory()->create();
        $user = User::factory()->withRole(Role::Coordinator)->create();
        $user->programs()->attach($own->programTerm->program_id);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        $action = TestAction::make('review')->table($own);
        Livewire::test(ListRequirements::class)
            ->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$outside])
            ->callAction($action, data: ['status' => 'rejected', 'comments' => ''])->assertHasFormErrors(['comments']);
        Livewire::test(ListRequirements::class)
            ->callAction($action, data: ['status' => 'approved', 'comments' => 'Synthetic QA review'])
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('requirement_submissions', ['id' => $own->id, 'status' => 'approved']);
        $this->assertDatabaseHas('requirement_reviews', ['requirement_submission_id' => $own->id, 'reviewed_by' => $user->id, 'decision' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.reviewed', 'actor_id' => $user->id]);
        $this->assertEquals(1, $own->studentEnrollment->student->user->notifications()->count());
        Livewire::test(ListRequirements::class)->assertActionHidden($action);
    }

    public function test_admin_cannot_acquire_academic_review_authority_through_panel(): void
    {
        $record = RequirementSubmission::factory()->create();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create());
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        Livewire::test(ListRequirements::class)->assertCanSeeTableRecords([$record])
            ->assertActionHidden(TestAction::make('review')->table($record));
        $this->assertDatabaseHas('requirement_submissions', ['id' => $record->id, 'status' => 'submitted']);
    }
}
