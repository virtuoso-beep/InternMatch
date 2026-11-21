<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The progress and evaluations lists load in one request. Query count must not grow with the number of
 * records: a full page costs the same whether 30 or 60 placements exist.
 */
class PlacementListPerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function placements(int $count, User $coordinator, ?StudentEnrollment $template = null): StudentEnrollment
    {
        $template ??= StudentEnrollment::factory()->create();
        $coordinator->programs()->syncWithoutDetaching([$template->programTerm->program_id]);
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        for ($i = 0; $i < $count; $i++) {
            $enrollment = StudentEnrollment::factory()->create(['program_term_id' => $template->program_term_id]);
            Placement::factory()->create(['student_enrollment_id' => $enrollment->id, 'status' => 'active', 'supervisor_id' => $supervisor->id, 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-18']);
        }

        return $template;
    }

    private function queries(User $user, string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->getJson($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_a_full_page_costs_the_same_with_more_records(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $template = $this->placements(30, $coordinator);
        $progress = $this->queries($coordinator, '/api/v1/placements?include=progress');
        $evaluations = $this->queries($coordinator, '/api/v1/placements?include=evaluations');
        $both = $this->queries($coordinator, '/api/v1/placements?include=progress,evaluations');

        $this->placements(30, $coordinator, $template);
        $this->assertSame($progress, $this->queries($coordinator, '/api/v1/placements?include=progress'), 'Progress list query count grew with the number of placements.');
        $this->assertSame($evaluations, $this->queries($coordinator, '/api/v1/placements?include=evaluations'), 'Evaluations list query count grew with the number of placements.');
        $this->assertSame($both, $this->queries($coordinator, '/api/v1/placements?include=progress,evaluations'), 'Intern list query count grew with the number of placements.');
        $this->assertLessThanOrEqual(25, $evaluations);
    }

    public function test_rows_include_progress_or_evaluations(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $this->placements(2, $coordinator);
        $this->actingAs($coordinator)->getJson('/api/v1/placements?include=progress')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonStructure(['data' => [['progress' => ['completed_minutes', 'flags']]]]);
        $this->getJson('/api/v1/placements?include=evaluations')->assertOk()->assertJsonStructure(['data' => [['evaluations']]]);
        $this->getJson('/api/v1/placements?include=progress,evaluations')->assertOk()->assertJsonStructure(['data' => [['progress', 'evaluations']]]);
        $this->getJson('/api/v1/placements?include=unknown')->assertUnprocessable();
    }
}
