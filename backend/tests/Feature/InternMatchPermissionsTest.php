<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EvaluationStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Evaluation;
use App\Models\Placement;
use App\Models\ProgramTerm;
use App\Models\RequirementSubmission;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InternMatchPermissionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function roles(): array
    {
        return [
            'student' => [Role::Student, false],
            'coordinator' => [Role::Coordinator, true],
            'supervisor' => [Role::Supervisor, false],
            'dean' => [Role::Dean, false],
            'admin' => [Role::Admin, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_only_an_assigned_coordinator_can_make_placement_decisions(Role $role, bool $allowed): void
    {
        $placement = Placement::factory()->create();
        $user = User::factory()->withRole($role)->create();
        $user->programTerms()->attach($placement->studentEnrollment->program_term_id);

        $this->assertSame($allowed, Gate::forUser($user)->allows('decide', $placement));
    }

    public function test_coordinator_cannot_decide_outside_assigned_program_term(): void
    {
        $placement = Placement::factory()->create();
        $user = User::factory()->withRole(Role::Coordinator)->create();
        $user->programTerms()->attach(ProgramTerm::factory()->create());

        $this->assertFalse(Gate::forUser($user)->allows('decide', $placement));
        $this->assertFalse(Gate::forUser($user)->allows('view', $placement));
    }

    public static function inactiveStatuses(): array
    {
        return ['pending' => [AccountStatus::Pending], 'disabled' => [AccountStatus::Disabled]];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_inactive_coordinators_have_no_placement_authority(AccountStatus $status): void
    {
        $placement = Placement::factory()->create();
        $user = User::factory()->withRole(Role::Coordinator)->create(['status' => $status]);
        $user->programTerms()->attach($placement->studentEnrollment->program_term_id);

        $this->assertFalse(Gate::forUser($user)->allows('decide', $placement));
    }

    public function test_unassigned_existing_account_receives_no_domain_permissions(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertNull($user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
        foreach (Permission::cases() as $permission) {
            $this->assertFalse($user->hasPermission($permission));
        }
    }

    public function test_profile_mass_assignment_cannot_change_role_or_account_status(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();

        $user->fill(['name' => 'Updated name', 'role' => Role::Admin, 'status' => AccountStatus::Disabled])->save();

        $this->assertSame(Role::Student, $user->fresh()->role);
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
        $this->assertSame('Updated name', $user->fresh()->name);
    }

    public function test_admin_manages_system_records_without_academic_decision_privileges(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $placement = Placement::factory()->create();

        $this->assertTrue($admin->hasPermission(Permission::ManageAccounts));
        $this->assertTrue($admin->hasPermission(Permission::ManageSystem));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $placement->hostEstablishment));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $placement->studentEnrollment->programTerm->program));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $placement));
        $this->assertFalse($admin->hasPermission(Permission::SubmitEvaluations));
        $this->assertFalse($admin->hasPermission(Permission::ReviewRequirements));
    }

    public function test_student_can_view_own_placement_but_not_another_students(): void
    {
        $own = Placement::factory()->create();
        $other = Placement::factory()->create();
        $student = $own->studentEnrollment->student->user;

        $this->assertTrue(Gate::forUser($student)->allows('view', $own));
        $this->assertFalse(Gate::forUser($student)->allows('view', $other));
        $this->assertTrue(Gate::forUser($student)->allows('submitRequirements', $own->studentEnrollment));
        $this->assertFalse(Gate::forUser($student)->allows('submitRequirements', $other->studentEnrollment));
    }

    public function test_withdrawn_student_cannot_submit_requirements(): void
    {
        $enrollment = StudentEnrollment::factory()->create(['status' => EnrollmentStatus::Withdrawn]);

        $this->assertFalse(Gate::forUser($enrollment->student->user)->allows('submitRequirements', $enrollment));
    }

    public function test_supervisor_needs_both_host_membership_and_intern_assignment(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['supervisor_id' => $supervisor->id]);
        $log = TimeLog::factory()->for($placement)->create();

        $this->assertFalse(Gate::forUser($supervisor)->allows('verify', $log));

        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $this->assertTrue(Gate::forUser($supervisor)->allows('verify', $log));
        $this->assertTrue(Gate::forUser($supervisor)->allows('view', $placement));
        $this->assertTrue(Gate::forUser($supervisor)->allows('update', $placement->hostEstablishment));

        $placement->update(['supervisor_id' => null]);
        $this->assertFalse(Gate::forUser($supervisor)->allows('verify', $log->fresh()));
    }

    public function test_supervisor_cannot_monitor_other_hosts_or_read_requirement_documents(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create();
        $submission = RequirementSubmission::factory()->create();

        $this->assertFalse(Gate::forUser($supervisor)->allows('view', $placement));
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $placement->hostEstablishment));
        $this->assertFalse(Gate::forUser($supervisor)->allows('view', $submission));
    }

    public function test_dean_has_read_only_access_to_assigned_program_terms(): void
    {
        $dean = User::factory()->withRole(Role::Dean)->create();
        $placement = Placement::factory()->create();
        $other = Placement::factory()->create();
        $dean->programTerms()->attach($placement->studentEnrollment->program_term_id);

        $this->assertTrue(Gate::forUser($dean)->allows('view', $placement));
        $this->assertFalse(Gate::forUser($dean)->allows('view', $other));
        $this->assertFalse(Gate::forUser($dean)->allows('decide', $placement));
        $this->assertFalse(Gate::forUser($dean)->allows('update', $placement->studentEnrollment->programTerm->program));
    }

    public function test_requirement_review_requires_coordinator_scope_and_a_submitted_revision(): void
    {
        $submission = RequirementSubmission::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();

        $this->assertFalse(Gate::forUser($coordinator)->allows('review', $submission));

        $coordinator->programTerms()->attach($submission->program_term_id);
        $this->assertTrue(Gate::forUser($coordinator)->allows('review', $submission));

        $submission->update(['status' => SubmissionStatus::Approved]);
        $this->assertFalse(Gate::forUser($coordinator)->allows('review', $submission));
    }

    public function test_dean_reporting_scope_does_not_grant_access_to_requirement_documents(): void
    {
        $submission = RequirementSubmission::factory()->create();
        $dean = User::factory()->withRole(Role::Dean)->create();
        $dean->programTerms()->attach($submission->program_term_id);

        $this->assertTrue($dean->hasPermission(Permission::ViewReports));
        $this->assertFalse(Gate::forUser($dean)->allows('view', $submission));
        $this->assertFalse(Gate::forUser($dean)->allows('review', $submission));
    }

    public function test_only_assigned_evaluator_can_edit_and_submit_a_draft(): void
    {
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['supervisor_id' => $supervisor->id]);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $evaluation = Evaluation::factory()->for($placement)->create(['evaluator_id' => $supervisor->id]);
        $student = $placement->studentEnrollment->student->user;

        $this->assertTrue(Gate::forUser($supervisor)->allows('submit', $evaluation));
        $this->assertFalse(Gate::forUser($student)->allows('view', $evaluation));

        $evaluation->update(['status' => EvaluationStatus::Submitted, 'submitted_at' => '2026-08-20 09:00:00']);
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $evaluation));
        $this->assertTrue(Gate::forUser($student)->allows('view', $evaluation));
    }
}
