<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Admin, dean and coordinator accounts are fixed real accounts; only supervisors and students are synthetic. */
class SyntheticStaffAccountsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function snapshot(User ...$users): array
    {
        return collect($users)->mapWithKeys(fn ($u) => [$u->id => (array) DB::table('users')->where('id', $u->id)->first(['name', 'email', 'password', 'status', 'role'])])->all();
    }

    private function synthetic(Role $role): User
    {
        $user = User::factory()->withRole($role)->create();
        DB::table('users')->where('id', $user->id)->update(['is_synthetic' => true]);

        return $user->fresh();
    }

    public function test_reassignment_moves_synthetic_staff_records_to_real_accounts_and_purge_still_removes_them(): void
    {
        $enrollment = StudentEnrollment::factory()->create(['is_synthetic' => true]);
        $program = $enrollment->programTerm->program;
        $other = Program::factory()->create();
        [$admin, $dean, $coordinator] = [User::factory()->withRole(Role::Admin)->create(), User::factory()->withRole(Role::Dean)->create(), User::factory()->withRole(Role::Coordinator)->create()];
        $dean->programs()->attach($other->id);
        $coordinator->programs()->attach($program->id);
        [$sAdmin, $sDean, $sCoord] = [$this->synthetic(Role::Admin), $this->synthetic(Role::Dean), $this->synthetic(Role::Coordinator)];
        $sDean->programs()->attach($program->id);
        $sCoord->programs()->attach($program->id);
        $before = $this->snapshot($admin, $dean, $coordinator);

        DB::table('coordinator_labels')->insert(['source_id' => 'LBL-1', 'student_id' => $enrollment->student_id, 'program_id' => $program->id, 'hte_code' => 'HTE-1', 'opportunity_code' => 'OPP-1', 'program_match' => 1, 'skill_match_score' => .5, 'distance_km' => 1, 'available_slots' => 1, 'has_slot' => 1, 'moa_valid' => 1, 'coordinator_decision' => 'Suitable', 'suitable' => 1, 'labeled_by' => $sCoord->id, 'is_synthetic' => true]);
        DB::table('audit_logs')->insert([
            ['actor_id' => $sCoord->id, 'program_id' => $program->id, 'action' => 'Created placement', 'subject_type' => 'placement', 'subject_id' => 1, 'changes' => '{}', 'created_at' => now(), 'is_synthetic' => true],
            ['actor_id' => $sCoord->id, 'program_id' => $program->id, 'action' => 'report.generated', 'subject_type' => 'report', 'subject_id' => 1, 'changes' => '{}', 'created_at' => now(), 'is_synthetic' => false],
        ]);
        DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => 'x', 'notifiable_type' => (new User)->getMorphClass(), 'notifiable_id' => $sAdmin->id, 'data' => '{}', 'is_synthetic' => true, 'created_at' => now(), 'updated_at' => now()]);
        $args = ['--admin' => $admin->email, '--dean' => $dean->email, '--coordinator' => [$program->code.'='.$coordinator->email]];

        // A coordinator who does not hold the program is refused; nothing changes.
        $this->artisan('internmatch:reassign-synthetic-staff', [...$args, '--coordinator' => [$program->code.'='.User::factory()->withRole(Role::Coordinator)->create()->email], '--force' => true])->assertFailed();
        $this->artisan('internmatch:reassign-synthetic-staff', $args)->assertSuccessful(); // dry run
        $this->assertDatabaseHas('users', ['id' => $sCoord->id]);
        $this->assertDatabaseHas('coordinator_labels', ['source_id' => 'LBL-1', 'labeled_by' => $sCoord->id]);

        $this->artisan('internmatch:reassign-synthetic-staff', [...$args, '--force' => true])->assertSuccessful();
        foreach ([$sAdmin, $sDean, $sCoord] as $gone) {
            $this->assertDatabaseMissing('users', ['id' => $gone->id]);
        }
        $this->assertDatabaseHas('coordinator_labels', ['source_id' => 'LBL-1', 'labeled_by' => $coordinator->id, 'is_synthetic' => true]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created placement', 'actor_id' => $coordinator->id, 'is_synthetic' => true]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.generated', 'actor_id' => null]); // real history never re-attributed
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id, 'is_synthetic' => true]);
        $this->assertSame($before, $this->snapshot($admin, $dean, $coordinator)); // password, status, name, email, role unchanged
        $this->assertEqualsCanonicalizing([$other->id, $program->id], $dean->programs()->pluck('programs.id')->all());
        $this->assertSame([$program->id], $coordinator->programs()->pluck('programs.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.program_access_updated', 'actor_id' => $admin->id, 'subject_id' => $dean->id]);

        $this->artisan('internmatch:purge-synthetic', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseMissing('coordinator_labels', ['source_id' => 'LBL-1']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'Created placement']);
        $this->assertSame(0, DB::table('notifications')->where('is_synthetic', true)->count());
        $this->assertSame($before, $this->snapshot($admin, $dean, $coordinator));
    }

    /** Minimal workbooks in the synthetic data-set layout. */
    private function workbooks(string $code): array
    {
        $sheets = [
            'program' => [
                'students' => [['student_id', 'user_id', 'full_name', 'email', 'contact_number', 'program', 'major', 'year_level', 'section', 'home_address', 'barangay', 'latitude', 'longitude', 'skills', 'career_interests', 'internship_requirements', 'requirements_status', 'account_status', 'is_synthetic'],
                    [990001, 'U-S1', 'Synthetic Student', 's1@example.test', '09001', $code, 'Major', 4, 'A', 'Addr, Tagum City', 'Brgy', 7.40, 125.80, 'Skill Synthetic Q', 'Audit', 'Resume', 'Complete', 'Active', 1]],
                'htes' => [['hte_id', 'establishment_name', 'complete_address', 'barangay', 'latitude', 'longitude', 'contact_person', 'supervisor_user_id', 'contact_number', 'email', 'establishment_description', 'industry', 'available_slots', 'programs_accepted', 'moa_status', 'moa_start_date', 'moa_expiration_date', 'establishment_status', 'is_synthetic'],
                    ['HTE-901', 'Test Host', '1 St, Tagum City', 'Brgy', 7.45, 125.81, 'Sup Person', 'U-SUP', '09002', 'host@example.test', 'Desc', 'Accounting', 3, $code, 'Active', '2025-01-01', '2027-01-01', 'Active', 1]],
                'opportunities' => [['opportunity_id', 'hte_id', 'opportunity_title', 'description', 'internship_duties', 'required_skills', 'eligible_program', 'available_slots', 'required_qualifications', 'status', 'date_created', 'date_updated', 'is_synthetic'],
                    ['OPP-901', 'HTE-901', 'Internship', 'Desc', 'Duties', 'Skill Synthetic Q', $code, 3, 'Q', 'Published', '2025-06-15', '2025-07-20', 1]],
                'competency_vocabulary' => [['competency_id', 'program', 'competency_name', 'description', 'validated_by', 'is_synthetic'], ['COMP-T-01', $code, 'Skill Synthetic Q', 'Desc', null, 1]],
                'coordinator_labels' => [['label_id', 'student_id', 'hte_id', 'opportunity_id', 'program', 'program_match', 'skill_match_score', 'distance_km', 'available_slots', 'has_slot', 'moa_valid', 'coordinator_decision', 'suitable', 'decision_reason', 'labeled_by', 'is_synthetic'],
                    ['LBL-T1', 990001, 'HTE-901', 'OPP-901', $code, 1, 0.5, 1.2, 3, 1, 1, 'Suitable', 1, 'Good', 'U-C', 1]],
                'placements' => [['placement_id', 'student_id', 'hte_id', 'opportunity_id', 'coordinator_id', 'supervisor_id', 'start_date', 'end_date', 'required_hours', 'completed_hours', 'placement_status', 'is_synthetic'],
                    ['PL-901', 990001, 'HTE-901', 'OPP-901', 'U-C', 'U-SUP', '2025-08-18', '2025-08-22', 400, 400, 'Completed', 1]],
                'daily_attendance' => [['attendance_id', 'placement_id', 'student_id', 'date', 'status', 'time_in', 'time_out', 'hours', 'approved_by', 'is_synthetic'], ['ATT-1', 'PL-901', 990001, '2025-08-18', 'Present', '08:00', '12:00', 4, 'U-SUP', 1]],
                'weekly_logs' => [['log_id', 'placement_id', 'student_id', 'week_no', 'week_start', 'days_present', 'hours', 'activities', 'learnings', 'status', 'approved_by', 'is_synthetic'], ['LOG-1', 'PL-901', 990001, 1, '2025-08-18', 1, 4, 'Work', 'Learned', 'Approved', 'U-SUP', 1]],
                'monitoring' => [['monitoring_id', 'placement_id', 'student_id', 'monitoring_date', 'hours_rendered', 'days_absent', 'attendance_status', 'weekly_logs_submitted', 'accomplishment_report', 'monitoring_visit', 'issues_concerns', 'monitoring_status', 'approved_by', 'is_synthetic'],
                    ['MON-1', 'PL-901', 990001, '2025-08-22', 4, 0, 'Complete', 1, 'Submitted', 'No', null, 'On Track', 'U-C', 1]],
                'evaluations' => [['evaluation_id', 'placement_id', 'student_id', 'supervisor_id', 'evaluation_rubric', 'score_1', 'score_2', 'score_3', 'average_score', 'overall_rating', 'comments', 'evaluation_date', 'coordinator_validated', 'is_synthetic'],
                    ['EVAL-901', 'PL-901', 990001, 'U-SUP', 'A; B; C', 5, 4, 3, 4, 'Very Satisfactory', 'Good', '2025-08-22', 'Yes', 1]],
            ],
            'all' => [
                'users' => [['user_id', 'full_name', 'email', 'role', 'program_assignment', 'account_status', 'is_synthetic'],
                    ['U-A', 'Syn Admin', 'syn.admin@example.test', 'Admin', null, 'Active', 1], ['U-D', 'Syn Dean', 'syn.dean@example.test', 'Dean', null, 'Active', 1],
                    ['U-C', 'Syn Coordinator', 'syn.coord@example.test', 'Coordinator', $code, 'Active', 1], ['U-SUP', 'Syn Supervisor', 'syn.sup@example.test', 'Supervisor', null, 'Active', 1],
                    ['U-S1', 'Synthetic Student', 's1@example.test', 'Student', $code, 'Active', 1]],
                'role_permissions' => [['permission_id', 'permission', 'Admin', 'Dean', 'Coordinator', 'Supervisor', 'Student'], ['PERM-01', 'Manage user accounts', 'Yes', 'No', 'No', 'No', 'No']],
                'notifications' => [['notification_id', 'user_id', 'type', 'message', 'created_at', 'read', 'is_synthetic'], ['NTF-1', 'U-S1', 'Placement', 'Placed.', '2025-08-16 09:00:00', 'No', 1]],
                'chat_messages' => [['message_id', 'thread_id', 'placement_id', 'sender_id', 'sender_role', 'message', 'sent_at', 'is_synthetic'], ['MSG-1', 'CHAT-PL-901', 'PL-901', 'U-C', 'Coordinator', 'Hello', '2025-08-19 09:00:00', 1]],
                'audit_logs' => [['audit_id', 'user_id', 'action', 'record_type', 'record_id', 'timestamp', 'is_synthetic'], ['AUD-1', 'U-C', 'Created placement', 'Placement', 'PL-901', '2025-08-16 09:00:00', 1]],
            ],
        ];
        $paths = [];
        foreach ($sheets as $name => $tabs) {
            $book = new Spreadsheet;
            $book->removeSheetByIndex(0);
            foreach ($tabs as $title => $rows) {
                $sheet = $book->createSheet()->setTitle($title);
                foreach ($rows as $r => $row) {
                    foreach (array_values($row) as $c => $value) {
                        if ($value !== null) {
                            $sheet->setCellValue([$c + 1, $r + 1], $value);
                        }
                    }
                }
            }
            $paths[$name] = tempnam(sys_get_temp_dir(), 'syn').'.xlsx';
            (new Xlsx($book))->save($paths[$name]);
        }

        return $paths;
    }

    public function test_import_maps_admin_dean_and_coordinator_rows_to_real_accounts_and_never_creates_them(): void
    {
        Notification::fake();
        $program = Program::factory()->create(['required_ojt_hours' => 400]);
        [$admin, $dean, $coordinator] = [User::factory()->withRole(Role::Admin)->create(), User::factory()->withRole(Role::Dean)->create(), User::factory()->withRole(Role::Coordinator)->create()];
        $coordinator->programs()->attach($program->id);
        $paths = $this->workbooks($program->code);
        $args = ['program_workbook' => $paths['program'], 'all_workbook' => $paths['all'], '--program' => $program->code, '--term-name' => 'Test Term', '--term-start' => '2025-08-01', '--term-end' => '2025-12-31',
            '--activate' => '990001', '--admin' => $admin->email, '--dean' => $dean->email, '--coordinator' => $coordinator->email];
        $users = DB::table('users')->count();
        $assignments = DB::table('coordinator_program')->count();

        // The dean does not hold the program: stop before writing anything (the import never assigns programs).
        $this->artisan('internmatch:import-synthetic', $args)->assertFailed();
        $this->assertSame($users, DB::table('users')->count());
        $this->assertFalse(DB::table('synthetic_source_rows')->exists());

        $dean->programs()->attach($program->id);
        $assignments = DB::table('coordinator_program')->count();
        $before = DB::table('users')->whereIn('id', [$admin->id, $dean->id, $coordinator->id])->orderBy('id')->get(['name', 'email', 'password', 'status'])->toArray();
        $this->artisan('internmatch:import-synthetic', $args)->assertSuccessful();

        $created = DB::table('users')->where('is_synthetic', true)->pluck('role')->sort()->values()->all();
        $this->assertSame(['student', 'supervisor'], $created);
        $this->assertSame(0, DB::table('users')->whereIn('email', ['syn.admin@example.test', 'syn.dean@example.test', 'syn.coord@example.test'])->count());
        $this->assertSame($assignments, DB::table('coordinator_program')->count());
        $this->assertDatabaseHas('coordinator_labels', ['source_id' => 'LBL-T1', 'labeled_by' => $coordinator->id]);
        $this->assertDatabaseHas('monitoring_records', ['source_id' => 'MON-1', 'approved_by' => $coordinator->id]);
        $this->assertDatabaseHas('chat_messages', ['source_id' => 'MSG-1', 'sender_id' => $coordinator->id, 'is_synthetic' => true]);
        $this->assertDatabaseHas('audit_logs', ['source_id' => 'AUD-1', 'actor_id' => $coordinator->id, 'is_synthetic' => true]);
        $this->assertDatabaseHas('account_activations', ['issued_by' => $admin->id]);
        $this->assertEquals($before, DB::table('users')->whereIn('id', [$admin->id, $dean->id, $coordinator->id])->orderBy('id')->get(['name', 'email', 'password', 'status'])->toArray());
        foreach ($paths as $path) {
            @unlink($path);
        }
    }

    public function test_account_identity_update_changes_only_name_and_email_with_audit(): void
    {
        $program = Program::factory()->create();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $dean = User::factory()->withRole(Role::Dean)->create(['name' => 'Dean Demo']);
        $dean->programs()->attach($program->id);
        $taken = User::factory()->withRole(Role::Student)->create();
        $archived = User::factory()->withRole(Role::Student)->create();
        $archived->delete();
        $before = DB::table('users')->find($dean->id);

        $this->artisan('internmatch:update-account-identity', ['email' => $dean->email, '--new-email' => $taken->email, '--actor' => $admin->email])->assertFailed();
        $this->artisan('internmatch:update-account-identity', ['email' => $dean->email, '--new-email' => $archived->email, '--actor' => $admin->email])->assertFailed();
        $this->artisan('internmatch:update-account-identity', ['email' => $dean->email, '--name' => 'X Y', '--actor' => $taken->email])->assertFailed(); // actor must be an admin
        $this->assertEquals($before, DB::table('users')->find($dean->id));

        $this->artisan('internmatch:update-account-identity', ['email' => $dean->email, '--name' => 'Gina Fe G. Israel', '--new-email' => 'new.dean@example.test', '--actor' => $admin->email])->assertSuccessful();
        $after = DB::table('users')->find($dean->id);
        $this->assertSame(['Gina Fe G. Israel', 'new.dean@example.test'], [$after->name, $after->email]);
        foreach (['password', 'status', 'role', 'email_verified_at', 'is_synthetic', 'must_change_password'] as $field) {
            $this->assertEquals($before->{$field}, $after->{$field}, $field);
        }
        $this->assertSame([$program->id], $dean->programs()->pluck('programs.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.identity_updated', 'actor_id' => $admin->id, 'subject_id' => $dean->id,
            'changes->before->email' => $before->email, 'changes->after->email' => 'new.dean@example.test', 'changes->after->name' => 'Gina Fe G. Israel']);
    }
}
