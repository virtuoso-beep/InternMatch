<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\OpportunityEligibility;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Archive (records with history), restore, and delete (never-used records only). */
class RecordArchiveTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array{0: StudentEnrollment, 1: Opportunity, 2: Moa} an eligible student, opportunity and agreement */
    private function eligible(): array
    {
        $this->travelTo(now()->setDate(2026, 9, 19));
        $enrollment = StudentEnrollment::factory()->create();
        $opportunity = Opportunity::factory()->create(['academic_term_id' => $enrollment->programTerm->academic_term_id]);
        $opportunity->programs()->attach($enrollment->programTerm->program_id, ['capacity' => 2]);
        DB::table('host_program_capacity')->insert([
            'host_establishment_id' => $opportunity->host_establishment_id,
            'academic_term_id' => $opportunity->academic_term_id,
            'program_id' => $enrollment->programTerm->program_id, 'capacity' => 2,
        ]);
        $moa = Moa::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id]);
        $moa->programs()->attach($enrollment->programTerm->program_id);

        return [$enrollment, $opportunity, $moa];
    }

    public function test_archived_records_are_hidden_from_lists_and_not_eligible_until_restored(): void
    {
        [$enrollment, $opportunity, $moa] = $this->eligible();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $service = app(OpportunityEligibility::class);
        $this->assertNotNull($service->inspect($enrollment, $opportunity));
        $this->actingAs($admin);

        foreach (['opportunities' => $opportunity, 'hosts' => $opportunity->hostEstablishment, 'moas' => $moa] as $segment => $record) {
            $this->postJson("/api/v1/{$segment}/{$record->id}/archive")->assertOk();
            $this->assertNotNull($record->fresh()->archived_at);
            $this->assertSame($admin->id, $record->fresh()->archived_by);
            $this->postJson("/api/v1/{$segment}/{$record->id}/archive")->assertConflict();
            $this->assertNotContains($record->id, collect($this->getJson("/api/v1/{$segment}")->json('data'))->pluck('id')->all());
            $this->assertContains($record->id, collect($this->getJson("/api/v1/{$segment}?include_archived=1")->json('data'))->pluck('id')->all());
            $this->assertNull($service->inspect($enrollment->fresh(), $opportunity->fresh()), $segment);
            $this->postJson("/api/v1/{$segment}/{$record->id}/restore")->assertOk();
            $this->assertNull($record->fresh()->archived_at);
            $this->postJson("/api/v1/{$segment}/{$record->id}/restore")->assertConflict();
            $this->assertNotNull($service->inspect($enrollment->fresh(), $opportunity->fresh()), $segment);
        }
        $this->assertDatabaseHas('audit_logs', ['action' => 'opportunity.archived', 'subject_id' => $opportunity->id]);
    }

    public function test_records_with_live_placements_cannot_be_archived_and_archived_records_cannot_be_edited(): void
    {
        [, $opportunity, $moa] = $this->eligible();
        $admin = User::factory()->withRole(Role::Admin)->create();
        Placement::factory()->create(['opportunity_id' => $opportunity->id, 'host_establishment_id' => $opportunity->host_establishment_id, 'moa_id' => $moa->id, 'status' => 'pending']);
        $this->actingAs($admin);
        $this->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertConflict();
        $this->postJson("/api/v1/hosts/{$opportunity->host_establishment_id}/archive")->assertConflict();
        $this->postJson("/api/v1/moas/{$moa->id}/archive")->assertConflict();
        $this->assertNull($opportunity->fresh()->archived_at);

        $host = HostEstablishment::factory()->create();
        $this->postJson("/api/v1/hosts/{$host->id}/archive")->assertOk();
        $this->patchJson("/api/v1/hosts/{$host->id}", ['name' => 'Renamed'])->assertConflict();
        $this->assertNotSame('Renamed', $host->fresh()->name);
    }

    public function test_only_never_used_records_can_be_deleted(): void
    {
        [, $opportunity, $moa] = $this->eligible();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin);

        // Used: an opportunity and an agreement reference this host.
        $this->deleteJson("/api/v1/hosts/{$opportunity->host_establishment_id}")->assertConflict();
        $this->assertDatabaseHas('host_establishments', ['id' => $opportunity->host_establishment_id]);

        // Used: a placement references this opportunity and agreement. Delete is refused; archive is the only way out.
        $placement = Placement::factory()->create(['opportunity_id' => $opportunity->id, 'host_establishment_id' => $opportunity->host_establishment_id, 'moa_id' => $moa->id, 'status' => 'completed', 'completed_at' => now(), 'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id, 'starts_on' => '2026-06-01', 'ends_on' => '2026-08-31']);
        $this->deleteJson("/api/v1/opportunities/{$opportunity->id}")->assertConflict();
        $this->deleteJson("/api/v1/moas/{$moa->id}")->assertConflict();
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity->id]);
        $this->assertDatabaseHas('moas', ['id' => $moa->id]);
        $this->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertOk();
        $this->postJson("/api/v1/moas/{$moa->id}/archive")->assertOk();
        $this->deleteJson("/api/v1/opportunities/{$opportunity->id}")->assertConflict();
        $this->assertDatabaseHas('placements', ['id' => $placement->id]);

        // Never used: deleted together with its own configuration rows.
        $fresh = Opportunity::factory()->create(['host_establishment_id' => $opportunity->host_establishment_id, 'academic_term_id' => $opportunity->academic_term_id]);
        $fresh->programs()->attach($opportunity->programs()->first()->id, ['capacity' => 1]);
        $this->deleteJson("/api/v1/opportunities/{$fresh->id}")->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $fresh->id]);
        $this->assertDatabaseMissing('opportunity_program', ['opportunity_id' => $fresh->id]);

        $host = HostEstablishment::factory()->create();
        $this->deleteJson("/api/v1/hosts/{$host->id}")->assertNoContent();
        $this->assertDatabaseHas('audit_logs', ['action' => 'host.deleted', 'subject_id' => $host->id]);

        $term = AcademicTerm::factory()->create(['is_active' => false]);
        $this->deleteJson("/api/v1/academic-terms/{$term->id}")->assertNoContent();
        $this->deleteJson("/api/v1/academic-terms/{$opportunity->academic_term_id}")->assertConflict();
    }

    public function test_terms_must_be_inactive_to_archive_and_archived_terms_cannot_be_activated(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $term = AcademicTerm::factory()->create(['is_active' => true]);
        $this->actingAs($admin)->postJson("/api/v1/academic-terms/{$term->id}/archive")->assertConflict();
        $this->patchJson("/api/v1/admin/terms/{$term->id}", ['is_active' => false])->assertOk();
        $this->postJson("/api/v1/academic-terms/{$term->id}/archive")->assertOk();
        $this->patchJson("/api/v1/admin/terms/{$term->id}", ['is_active' => true])->assertConflict();
        $this->assertNotContains($term->id, collect($this->getJson('/api/v1/placement-reference')->json('terms'))->pluck('id')->all());
        $this->postJson("/api/v1/academic-terms/{$term->id}/restore")->assertOk();
        $this->patchJson("/api/v1/admin/terms/{$term->id}", ['is_active' => true])->assertOk();
    }

    public function test_role_scoping_for_archive_restore_and_delete(): void
    {
        [$enrollment, $opportunity, $moa] = $this->eligible();
        $host = $opportunity->hostEstablishment;
        $term = AcademicTerm::factory()->create(['is_active' => false]);

        $own = User::factory()->withRole(Role::Coordinator)->create();
        $own->programs()->attach($enrollment->programTerm->program_id);
        $this->actingAs($own)->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertOk();
        $this->postJson("/api/v1/opportunities/{$opportunity->id}/restore")->assertOk();
        $this->postJson("/api/v1/moas/{$moa->id}/archive")->assertOk();
        $this->postJson("/api/v1/moas/{$moa->id}/restore")->assertOk();
        $this->postJson("/api/v1/academic-terms/{$term->id}/archive")->assertForbidden();

        // A coordinator cannot archive a record that another program also depends on.
        $other = Program::factory()->create();
        $opportunity->programs()->attach($other->id, ['capacity' => 1]);
        $this->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertForbidden();
        $this->postJson("/api/v1/hosts/{$host->id}/archive")->assertForbidden();

        $outsider = User::factory()->withRole(Role::Coordinator)->create();
        $this->actingAs($outsider)->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertNotFound();

        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $supervisor->hostEstablishments()->attach($host->id);
        foreach ([$supervisor, User::factory()->withRole(Role::Dean)->create(), $enrollment->student->user] as $user) {
            $this->actingAs($user)->postJson("/api/v1/hosts/{$host->id}/archive")->assertForbidden();
            $this->postJson("/api/v1/opportunities/{$opportunity->id}/archive")->assertForbidden();
            $this->postJson("/api/v1/moas/{$moa->id}/archive")->assertForbidden();
            $this->deleteJson("/api/v1/academic-terms/{$term->id}")->assertForbidden();
        }
        $this->assertNull($opportunity->fresh()->archived_at);
        $this->assertNull($host->fresh()->archived_at);
    }
}
