<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\StudentAccess;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoordinatorAssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function assign(User $admin, User $user, array $programIds)
    {
        return $this->actingAs($admin)->putJson('/api/v1/users/'.$user->id.'/programs', ['program_ids' => $programIds]);
    }

    public function test_coordinator_holds_one_program_and_moving_keeps_history_and_moves_access(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $first = StudentEnrollment::factory()->create();
        $second = StudentEnrollment::factory()->create();
        $a = $first->programTerm->program_id;
        $b = $second->programTerm->program_id;

        $this->assign($admin, $coordinator, [$a, $b])->assertUnprocessable()->assertJsonValidationErrors('program_ids');
        $this->assertSame(0, DB::table('coordinator_program')->where('user_id', $coordinator->id)->count());

        $this->assign($admin, $coordinator, [$a])->assertOk();
        $this->assertTrue(StudentAccess::enrollments($coordinator->fresh())->whereKey($first->id)->exists());

        $this->assign($admin, $coordinator, [$b])->assertOk();
        $coordinator = $coordinator->fresh();
        $this->assertSame([$b], $coordinator->programs()->pluck('programs.id')->all());
        $this->assertFalse(StudentAccess::enrollments($coordinator)->whereKey($first->id)->exists());
        $this->assertTrue(StudentAccess::enrollments($coordinator)->whereKey($second->id)->exists());
        // The ended assignment is kept as history, not deleted.
        $this->assertNotNull(DB::table('coordinator_program')->where(['user_id' => $coordinator->id, 'program_id' => $a])->value('removed_at'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.program_access_updated', 'subject_id' => $coordinator->id]);

        // Removing and later re-assigning the same program is allowed.
        $this->assign($admin, $coordinator, [])->assertOk();
        $this->assign($admin, $coordinator, [$b])->assertOk();
        $this->assertSame(3, DB::table('coordinator_program')->where('user_id', $coordinator->id)->whereNotNull('removed_at')->count() + 1);
        $this->assertSame(1, DB::table('coordinator_program')->where('user_id', $coordinator->id)->whereNull('removed_at')->count());
    }

    public function test_program_may_have_several_coordinators_and_removing_one_leaves_the_others(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $program = Program::factory()->create();
        [$one, $two] = User::factory()->withRole(Role::Coordinator)->count(2)->create();

        $this->assign($admin, $one, [$program->id])->assertOk();
        $this->assign($admin, $two, [$program->id])->assertOk();
        $this->assertSame(2, $program->coordinators()->count());

        $this->assign($admin, $one, [])->assertOk();
        $this->assertSame([$two->id], $program->fresh()->coordinators()->pluck('users.id')->all());
    }

    public function test_deans_may_hold_several_programs_and_role_change_to_coordinator_requires_one(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $dean = User::factory()->withRole(Role::Dean)->create();
        [$a, $b] = Program::factory()->count(2)->create();

        $this->assign($admin, $dean, [$a->id, $b->id])->assertOk();
        $this->assertSame(2, $dean->programs()->count());

        $this->actingAs($admin)->patchJson('/api/v1/users/'.$dean->id.'/access', ['role' => 'coordinator'])
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame(Role::Dean, $dean->fresh()->role);
    }
}
