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
use Tests\Concerns\AcknowledgesPrivacyNotice;
use Tests\TestCase;

class FilamentRequirementTest extends TestCase
{
    use AcknowledgesPrivacyNotice;
    use LazilyRefreshDatabase;

    public function test_admin_panel_lists_requirements_but_cannot_review_and_coordinator_review_works_via_api(): void
    {
        $own = RequirementSubmission::factory()->create();
        $outside = RequirementSubmission::factory()->create();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        // Administrators see records but hold no academic approval authority.
        Livewire::test(ListRequirements::class)->assertCanSeeTableRecords([$own, $outside])
            ->assertActionHidden(TestAction::make('review')->table($own));
        $user = User::factory()->withRole(Role::Coordinator)->create();
        $user->programs()->attach($own->programTerm->program_id);
        $this->actingAs($user);
        $url = '/api/v1/submissions/'.$own->id.'/review';
        $this->postJson($url, ['status' => 'rejected', 'comments' => ''])->assertUnprocessable();
        $this->postJson('/api/v1/submissions/'.$outside->id.'/review', ['status' => 'approved', 'comments' => 'x'])->assertForbidden();
        $this->postJson($url, ['status' => 'approved', 'comments' => 'Synthetic QA review'])->assertSuccessful();
        $this->assertDatabaseHas('requirement_submissions', ['id' => $own->id, 'status' => 'approved']);
        $this->assertDatabaseHas('requirement_reviews', ['requirement_submission_id' => $own->id, 'reviewed_by' => $user->id, 'decision' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.reviewed', 'actor_id' => $user->id]);
        $this->assertEquals(1, $own->studentEnrollment->student->user->notifications()->count());
    }
}
