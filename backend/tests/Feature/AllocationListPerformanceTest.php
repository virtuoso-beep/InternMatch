<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\PlacementReadiness;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pending approvals and the coordinator dashboard check placement readiness for every student in scope.
 * The query count must not grow with the number of students.
 */
class AllocationListPerformanceTest extends TestCase
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

    private function queries(User $user, string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->getJson($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_query_count_does_not_grow_with_the_number_of_students(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = $this->cohort(3, $coordinator);
        $allocation = $this->queries($coordinator, '/api/v1/allocation');
        $dashboard = $this->queries($coordinator, '/api/v1/dashboard');

        $this->cohort(25, $coordinator, $first);
        $this->assertSame($allocation, $this->queries($coordinator, '/api/v1/allocation'), 'Pending approvals query count grew with the number of students.');
        $this->assertSame($dashboard, $this->queries($coordinator, '/api/v1/dashboard'), 'Coordinator dashboard query count grew with the number of students.');
        $this->assertLessThanOrEqual(20, $allocation);
    }

    public function test_batch_reasons_match_single_reasons(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = $this->cohort(4, $coordinator);
        $enrollments = StudentEnrollment::where('program_term_id', $first->program_term_id)->get();
        $readiness = app(PlacementReadiness::class);

        $batch = $readiness->reasonsForMany($enrollments);
        $unmet = $readiness->unmetRequirementsForMany(new Collection($enrollments->all()));
        foreach ($enrollments as $enrollment) {
            $fresh = StudentEnrollment::find($enrollment->id);
            $this->assertSame($readiness->reasons($fresh), $batch[$enrollment->id]);
            $this->assertSame($readiness->unmetRequirements($fresh), $unmet[$enrollment->id]);
        }
        $this->assertSame([], $readiness->reasonsForMany(new Collection));
    }
}
