<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Recommendation;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchingPersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function snapshot(): int
    {
        return DB::table('recommendations')->insertGetId([
            'generation_id' => (string) Str::uuid(), 'student_enrollment_id' => StudentEnrollment::factory()->create()->id,
            'opportunity_id' => Opportunity::factory()->create()->id, 'similarity_score' => 0.8,
            'distance_km' => 3.2, 'capacity_at_time' => 2, 'moa_status_at_time' => 'active', 'rank' => 1,
            'ranking_method' => 'cosine_with_placement_criteria',
            'snapshot' => json_encode(['model_name' => 'intfloat/multilingual-e5-base', 'model_version' => 'test-fixture-only', 'source_text_hash' => hash('sha256', 'fixture')]),
            'generated_at' => now(),
        ]);
    }

    public function test_recommendation_retains_generation_values_after_source_changes(): void
    {
        $id = $this->snapshot();
        $before = DB::table('recommendations')->find($id);
        Opportunity::findOrFail($before->opportunity_id)->update(['capacity' => 0, 'status' => 'closed']);
        $this->assertEquals($before, DB::table('recommendations')->find($id));
        $student = StudentEnrollment::findOrFail($before->student_enrollment_id);
        $this->actingAs($student->student->user)->getJson('/api/v1/enrollments/'.$student->id.'/recommendations')
            ->assertOk()->assertJsonPath('data.0.capacity_at_time', 2)->assertJsonPath('data.0.distance_km', 3.2);
        $this->actingAs(StudentEnrollment::factory()->create()->student->user)
            ->getJson('/api/v1/enrollments/'.$student->id.'/recommendations')->assertNotFound();
    }

    public function test_model_prevents_mutating_frozen_explanations(): void
    {
        $id = $this->snapshot();
        $this->expectException(\LogicException::class);
        Recommendation::findOrFail($id)->update(['capacity_at_time' => 99]);
    }

    public function test_proposal_cannot_include_a_student_from_another_program_term(): void
    {
        $student = StudentEnrollment::factory()->create();
        $other = StudentEnrollment::factory()->create();
        $proposal = DB::table('placement_proposals')->insertGetId([
            'program_term_id' => $student->program_term_id, 'created_by' => User::factory()->create()->id,
            'constraints_snapshot' => '{}',
        ]);
        $this->expectException(QueryException::class);
        DB::table('placement_proposal_items')->insert([
            'placement_proposal_id' => $proposal, 'program_term_id' => $student->program_term_id,
            'student_enrollment_id' => $other->id,
        ]);
    }
}
