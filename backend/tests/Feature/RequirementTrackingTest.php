<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The coordinator requirements page loads a page of enrollments in one request with a fixed number of queries. */
class RequirementTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function cohort(int $students, User $coordinator, ?StudentEnrollment $first = null): StudentEnrollment
    {
        $first ??= StudentEnrollment::factory()->create();
        $coordinator->programs()->syncWithoutDetaching([$first->programTerm->program_id]);
        $requirement = ProgramTermRequirement::query()->firstOrCreate(
            ['program_term_id' => $first->program_term_id, 'requirement_type_id' => ProgramTermRequirement::factory()->make()->requirement_type_id],
            ['is_required' => true, 'required_before_deployment' => true],
        );
        for ($i = 0; $i < $students; $i++) {
            $enrollment = StudentEnrollment::factory()->create(['program_term_id' => $first->program_term_id]);
            RequirementSubmission::factory()->create(['student_enrollment_id' => $enrollment->id, 'program_term_requirement_id' => $requirement->id, 'program_term_id' => $first->program_term_id]);
        }

        return $first;
    }

    private function queriesFor(User $user, string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->getJson($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_query_count_does_not_grow_with_the_number_of_enrollments(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = $this->cohort(3, $coordinator);
        $small = $this->queriesFor($coordinator, '/api/v1/requirement-tracking?per_page=50');

        $this->cohort(25, $coordinator, $first);
        $large = $this->queriesFor($coordinator, '/api/v1/requirement-tracking?per_page=50');

        $this->assertSame($small, $large, "Query count grew from {$small} to {$large} when enrollments went from 4 to 29.");
        $this->assertLessThanOrEqual(25, $large);
    }

    public function test_returns_one_page_with_requirements_submissions_and_a_scope_wide_summary(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = $this->cohort(24, $coordinator);
        $response = $this->actingAs($coordinator)->getJson('/api/v1/requirement-tracking?per_page=10')->assertOk()
            ->assertJsonPath('total', 25)->assertJsonPath('last_page', 3)->assertJsonCount(10, 'data')
            ->assertJsonPath('summary.enrollments', 25)->assertJsonPath('summary.total', 25)
            ->assertJsonPath('summary.missing', 1);
        $row = collect($response->json('data'))->firstWhere('id', '!=', $first->id);
        $this->assertCount(1, $row['requirements']);
        $this->assertCount(1, $row['submissions']);
    }

    public function test_scope_and_synthetic_switch(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = $this->cohort(2, $coordinator);
        $synthetic = StudentEnrollment::factory()->create(['program_term_id' => $first->program_term_id, 'is_synthetic' => true]);
        $other = StudentEnrollment::factory()->create();

        $ids = fn (string $url) => collect($this->actingAs($coordinator)->getJson($url)->assertOk()->json('data'))->pluck('id');
        $this->assertNotContains($synthetic->id, $ids('/api/v1/requirement-tracking'));
        $this->assertContains($synthetic->id, $ids('/api/v1/requirement-tracking?include_synthetic=1'));
        $this->assertNotContains($other->id, $ids('/api/v1/requirement-tracking?include_synthetic=1'));

        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->assertContains($other->id, collect($this->actingAs($admin)->getJson('/api/v1/requirement-tracking?per_page=50')->json('data'))->pluck('id'));
        foreach ([Role::Student, Role::Supervisor, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/requirement-tracking')->assertForbidden();
        }
    }
}
