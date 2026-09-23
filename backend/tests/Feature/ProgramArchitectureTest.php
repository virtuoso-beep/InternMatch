<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProgramArchitectureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reference_seed_has_sixteen_programs_without_inventing_hours_or_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(16, Program::count());
        $this->assertSame(0, User::count());
        $this->assertSame(486, Program::where('code', 'BSIT')->firstOrFail()->required_ojt_hours);
        $this->assertSame(0, Program::where('code', '!=', 'BSIT')->whereNotNull('required_ojt_hours')->count());
        foreach (Program::withCount('competencies')->get() as $program) {
            $this->assertSame(strtoupper($program->code), $program->code);
            $this->assertSame(15, $program->competencies_count);
            $this->assertNotEmpty($program->cluster);
        }
        $bsit = Program::where('code', 'BSIT')->firstOrFail();
        $bsit->update(['required_ojt_hours' => 420, 'internship_term' => '4th year, second semester']);
        $user = User::factory()->withRole(Role::Student)->create(['email' => 'student@example.com']);
        $password = $user->password;
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame(420, $bsit->fresh()->required_ojt_hours);
    }

    public function test_coordinator_program_access_applies_across_terms_and_excludes_other_programs(): void
    {
        $placement = Placement::factory()->create();
        $other = Placement::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $program = $placement->studentEnrollment->programTerm->program;
        $secondTerm = ProgramTerm::factory()->for($program)->create();
        $coordinator->programs()->attach($program);
        $this->assertTrue($coordinator->isAssignedToProgramTerm($secondTerm->id));
        $this->assertTrue(Gate::forUser($coordinator)->allows('decide', $placement));
        $this->assertFalse(Gate::forUser($coordinator)->allows('decide', $other));
        $this->actingAs($coordinator)->getJson('/api/v1/programs')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $program->id);
    }

    public function test_legacy_term_assignment_alone_does_not_grant_new_program_access(): void
    {
        $placement = Placement::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programTerms()->attach($placement->studentEnrollment->program_term_id);
        $this->assertFalse(Gate::forUser($coordinator)->allows('decide', $placement));
    }

    public function test_admin_can_set_and_clear_program_requirements_with_an_audit_record(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $program = Program::factory()->create();
        $this->actingAs($admin)->patchJson('/api/v1/programs/'.$program->id, [
            'required_ojt_hours' => 420, 'internship_term' => '4th year',
        ])->assertOk()->assertJsonPath('data.required_ojt_hours', 420);
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'required_ojt_hours' => 420]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'program.updated', 'program_id' => $program->id]);
        $this->patchJson('/api/v1/programs/'.$program->id, ['required_ojt_hours' => null, 'internship_term' => null])
            ->assertOk()->assertJsonPath('data.required_ojt_hours', null);
    }

    public function test_program_update_validates_hours_and_denies_non_administrators(): void
    {
        $program = Program::factory()->create();
        $data = ['required_ojt_hours' => 420, 'internship_term' => '4th year'];
        $this->patchJson('/api/v1/programs/'.$program->id, $data)->assertUnauthorized();
        foreach ([Role::Student, Role::Coordinator, Role::Supervisor, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())
                ->patchJson('/api/v1/programs/'.$program->id, $data)->assertForbidden();
        }
        $this->actingAs(User::factory()->withRole(Role::Admin)->create());
        foreach ([0, -1, 1.5, 10001] as $hours) {
            $this->patchJson('/api/v1/programs/'.$program->id, ['required_ojt_hours' => $hours, 'internship_term' => null])
                ->assertUnprocessable()->assertJsonValidationErrors('required_ojt_hours');
        }
    }

    public function test_admin_can_assign_and_revoke_program_access_but_cannot_approve_placements(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $placement = Placement::factory()->create();
        $programId = $placement->studentEnrollment->programTerm->program_id;
        $this->actingAs($admin)->putJson('/api/v1/users/'.$coordinator->id.'/programs', ['program_ids' => [$programId]])->assertOk();
        $this->assertTrue(Gate::forUser($coordinator)->allows('decide', $placement));
        $this->assertFalse(Gate::forUser($admin)->allows('decide', $placement));
        $this->putJson('/api/v1/users/'.$coordinator->id.'/programs', ['program_ids' => []])->assertOk();
        $this->assertFalse(Gate::forUser($coordinator)->allows('decide', $placement));
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_staff_cannot_self_assign_programs_or_assign_unknown_programs(): void
    {
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $program = Program::factory()->create();
        $this->actingAs($coordinator)->putJson('/api/v1/users/'.$coordinator->id.'/programs', ['program_ids' => [$program->id]])->assertForbidden();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->putJson('/api/v1/users/'.$coordinator->id.'/programs', ['program_ids' => [999999]])->assertUnprocessable();
        $this->assertSame(0, $coordinator->programs()->count());
    }

    public function test_capacity_is_independent_for_each_eligible_program_and_is_not_inferred(): void
    {
        $opportunity = Opportunity::factory()->create(['capacity' => 5]);
        $it = Program::factory()->create();
        $cs = Program::factory()->create();
        $unknown = Program::factory()->create();
        $opportunity->programs()->attach([$it->id => ['capacity' => 3], $cs->id => ['capacity' => 2], $unknown->id => ['capacity' => null]]);
        $this->assertSame(3, (int) $opportunity->programs()->find($it->id)->pivot->capacity);
        $this->assertSame(2, (int) $opportunity->programs()->find($cs->id)->pivot->capacity);
        $this->assertNull($opportunity->programs()->find($unknown->id)->pivot->capacity);
    }

    public function test_moa_without_program_coverage_is_not_assumed_institution_wide(): void
    {
        $moa = Moa::factory()->create();
        $it = Program::factory()->create();
        $cs = Program::factory()->create();
        $this->assertFalse($moa->coversProgram($it->id));
        $moa->programs()->attach($it);
        $this->assertTrue($moa->coversProgram($it->id));
        $this->assertFalse($moa->coversProgram($cs->id));
        $moa->update(['is_institution_wide' => true]);
        $this->assertTrue($moa->coversProgram($cs->id));
    }

    public function test_database_rejects_zero_required_hours(): void
    {
        $program = Program::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('programs')->where('id', $program->id)->update(['required_ojt_hours' => 0]);
    }
}
