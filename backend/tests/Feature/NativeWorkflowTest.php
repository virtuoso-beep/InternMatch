<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Allocations\Pages\ListAllocations;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Widgets\PlacementStatistics;
use App\Models\GeneratedReport;
use App\Models\PlacementProposalItem;
use App\Models\ProgramTerm;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\CohortAllocation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NativeWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actor(ProgramTerm $term): User
    {
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($term->program_id);
        $this->actingAs($actor);
        Filament::setCurrentPanel(Filament::getPanel('manage'));

        return $actor;
    }

    public function test_native_allocation_scopes_records_and_audits_final_rejection(): void
    {
        $own = StudentEnrollment::factory()->create();
        $actor = $this->actor($own->programTerm);
        $proposal = app(CohortAllocation::class)->generate($actor, $own->programTerm);
        $item = PlacementProposalItem::where('placement_proposal_id', $proposal)->firstOrFail();
        $outside = StudentEnrollment::factory()->create();
        $otherActor = User::factory()->withRole(Role::Coordinator)->create();
        $otherActor->programs()->attach($outside->programTerm->program_id);
        $otherProposal = app(CohortAllocation::class)->generate($otherActor, $outside->programTerm);
        $other = PlacementProposalItem::where('placement_proposal_id', $otherProposal)->firstOrFail();
        Livewire::test(ListAllocations::class)->assertCanSeeTableRecords([$item])->assertCanNotSeeTableRecords([$other])
            ->callTableAction('decide', $item, ['decision' => 'reject', 'reason' => 'Missing academic clearance for this test.'])->assertHasNoTableActionErrors();
        $this->assertDatabaseHas('placement_proposal_items', ['id' => $item->id, 'review_status' => 'rejected', 'reviewed_by' => $actor->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'allocation.reject', 'actor_id' => $actor->id]);
        $this->assertDatabaseCount('placements', 0);
        Livewire::test(ListAllocations::class)->assertTableActionHidden('decide', $item->fresh());
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->get('/manage/allocations')->assertForbidden();
    }

    public function test_native_report_generation_approval_and_exports_share_api_scope(): void
    {
        $e = StudentEnrollment::factory()->create();
        $actor = $this->actor($e->programTerm);
        Livewire::test(ListReports::class)->callAction('generate', ['program_term_id' => $e->program_term_id, 'kind' => 'progress'])->assertHasNoActionErrors();
        $report = GeneratedReport::firstOrFail();
        $this->assertSame($e->student->user->name, $report->payload['rows'][0][1]);
        $hash = $report->content_hash;
        Livewire::test(ListReports::class)->assertCanSeeTableRecords([$report])->callTableAction('approve', $report)->assertHasNoTableActionErrors();
        $this->assertSame($hash, $report->fresh()->content_hash);
        $this->assertSame($actor->id, $report->fresh()->approved_by);
        $this->get('/api/v1/reports/'.$report->id.'/download')->assertOk()->assertDownload();
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create())->getJson('/api/v1/reports/'.$report->id)->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->get('/manage/reports')->assertForbidden();
    }

    public function test_native_statistics_do_not_count_outside_program_enrollments(): void
    {
        $e = StudentEnrollment::factory()->create();
        StudentEnrollment::factory()->count(3)->create();
        $this->actor($e->programTerm);
        $widget = Livewire::test(PlacementStatistics::class);
        $stats = (new \ReflectionMethod(PlacementStatistics::class, 'getStats'))->invoke($widget->instance());
        $this->assertSame(1, $stats[0]->getValue());
        $this->assertSame(0, $stats[1]->getValue());
    }
}
