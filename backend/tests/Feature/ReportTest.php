<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{Evaluation, EvaluationScore, Placement, ProgramTermRequirement, StudentEnrollment, TimeLog, User};
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_all_report_kinds_match_source_data_and_snapshots_remain_frozen(): void
    {
        $e = StudentEnrollment::factory()->create();
        $e->student->user->update(['name' => '=Unsafe formula name']);
        $e->programTerm->program->update(['required_ojt_hours' => 486]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($e->programTerm->program_id);
        $p = Placement::factory()->create(['student_enrollment_id' => $e->id, 'status' => 'approved']);
        TimeLog::factory()->create(['placement_id' => $p->id, 'status' => 'verified', 'credited_minutes' => 480, 'verified_by' => $coordinator->id, 'verified_at' => now()]);
        ProgramTermRequirement::factory()->create(['program_term_id' => $e->program_term_id]);
        $evaluation = Evaluation::factory()->create(['placement_id' => $p->id, 'status' => 'submitted', 'submitted_at' => now()]);
        EvaluationScore::factory()->create(['evaluation_id' => $evaluation->id]);
        $generation = (string) Str::uuid();
        DB::table('recommendation_generations')->insert(['generation_id' => $generation, 'student_enrollment_id' => $e->id, 'created_by' => $coordinator->id, 'generated_at' => now()]);
        DB::table('recommendations')->insert(['generation_id' => $generation, 'student_enrollment_id' => $e->id, 'opportunity_id' => $p->opportunity_id, 'similarity_score' => .8, 'distance_km' => 2, 'capacity_at_time' => 3, 'moa_status_at_time' => 'active', 'rank' => 1, 'ranking_method' => 'cosine_then_distance', 'snapshot' => json_encode(['host_name' => 'Frozen host', 'opportunity_title' => 'Frozen position']), 'generated_at' => now()]);
        $this->actingAs($coordinator);
        $reports = [];
        foreach (ReportBuilder::KINDS as $kind) {
            $response = $this->postJson('/api/v1/reports', ['program_term_id' => $e->program_term_id, 'kind' => $kind])->assertCreated()->assertJsonCount(1, 'data.payload.rows');
            $reports[$kind] = $response->json('data');
        }
        $this->assertEquals(8, $reports['progress']['payload']['rows'][0][5]);
        $this->assertEquals(478, $reports['progress']['payload']['rows'][0][6]);
        $this->assertEquals(80, $reports['evaluation']['payload']['rows'][0][6]);
        $this->assertSame('Frozen host', $reports['recommendation']['payload']['rows'][0][2]);
        $this->assertSame(1, $reports['summary']['payload']['rows'][0][3]);
        $e->student->user->update(['name' => 'Changed name']);
        $this->getJson('/api/v1/reports/'.$reports['progress']['id'])->assertJsonPath('data.payload.rows.0.1', '=Unsafe formula name');
        $csv = $this->get('/api/v1/reports/'.$reports['progress']['id'].'/download')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=Unsafe formula name", $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_dean_sees_only_approved_assigned_reports_and_cannot_approve_or_generate(): void
    {
        $e = StudentEnrollment::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $dean = User::factory()->withRole(Role::Dean)->create();
        foreach ([$coordinator, $dean] as $user) { $user->programs()->attach($e->programTerm->program_id); }
        $id = $this->actingAs($coordinator)->postJson('/api/v1/reports', ['program_term_id' => $e->program_term_id, 'kind' => 'summary'])->assertCreated()->json('data.id');
        $this->actingAs($dean)->getJson('/api/v1/reports/'.$id)->assertNotFound();
        $this->getJson('/api/v1/reports/'.$id.'/download')->assertNotFound();
        $this->postJson('/api/v1/reports/'.$id.'/approve')->assertForbidden();
        $this->postJson('/api/v1/reports', ['program_term_id' => $e->program_term_id, 'kind' => 'summary'])->assertForbidden();
        $this->actingAs($coordinator)->postJson('/api/v1/reports/'.$id.'/approve')->assertOk();
        $this->actingAs($dean)->getJson('/api/v1/reports/'.$id)->assertOk();
        $this->getJson('/api/v1/reports')->assertJsonCount(1, 'reports.data');
        $this->actingAs(User::factory()->withRole(Role::Dean)->create())->getJson('/api/v1/reports/'.$id)->assertNotFound();
        $this->actingAs($e->student->user)->getJson('/api/v1/reports')->assertForbidden();
    }
}
