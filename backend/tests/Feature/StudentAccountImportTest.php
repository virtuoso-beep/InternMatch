<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\AccountActivationLink;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StudentAccountImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function setupData(): array
    {
        $program = Program::factory()->create(['code' => 'BSIT', 'required_ojt_hours' => 486]);
        $term = AcademicTerm::factory()->create();
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($program);
        $this->actingAs($actor);

        return [$actor, $program, $term];
    }

    private function row(array $changes = []): array
    {
        return array_replace(['kind' => 'student', 'student_id' => '001234', 'first_name' => 'Test', 'middle_name' => '', 'last_name' => 'Student', 'program' => 'BSIT', 'year_level' => '4', 'email' => 'test.student@example.com'], $changes);
    }

    private function preview(User $actor, Program $program, AcademicTerm $term, array $rows): array
    {
        $id = (string) Str::uuid();
        DB::table('excel_imports')->insert(['id' => $id, 'user_id' => $actor->id, 'module' => 'student', 'filename' => 'students.xlsx', 'workbook' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        $preview = $this->postJson("/api/v1/coordinator-imports/$id/preview", ['program_id' => $program->id, 'academic_term_id' => $term->id, 'rows' => $rows])->assertOk()->json();

        return [$id, $preview];
    }

    private function confirm(string $id, array $preview)
    {
        return $this->postJson("/api/v1/coordinator-imports/$id/confirm", ['revision' => $preview['revision']]);
    }

    public function test_seven_column_workbook_in_both_excel_formats_and_template(): void
    {
        [$actor, $program, $term] = $this->setupData();
        Notification::fake();
        $headers = ['Student ID', 'First Name', 'Middle Name', 'Last Name', 'Program', 'Academic Term', 'Year Level', 'Email'];
        $template = $this->get('/api/v1/coordinator-imports/student-template')->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'student_template');
        try {
            file_put_contents($path, $template);
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame($headers, $sheet->rangeToArray('A1:H1')[0]);
            $this->assertSame('@', $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());
            $sheet->fromArray([['001234', 'Test', null, 'Student', 'BSIT', $term->code, 4, 'test.student@example.com']], null, 'A2');
            foreach (['Xlsx' => 'xlsx', 'Xls' => 'xls'] as $writer => $extension) {
                IOFactory::createWriter($book, $writer)->save($path);
                $upload = $this->post('/api/v1/coordinator-imports', ['module' => 'student', 'file' => new UploadedFile($path, 'students.'.$extension, null, null, true)], ['Accept' => 'application/json'])->assertCreated()->json();
                $id = $upload['id'];
                $mapping = $upload['sheets'][0]['mapping'];
                $this->assertCount(8, $mapping);
                $rows = $this->postJson("/api/v1/coordinator-imports/$id/map", ['sheet' => 0, 'mapping' => $mapping])->assertOk()->json('rows');
                $this->assertSame('001234', $rows[0]['student_id']);
                $rows[0]['enrolled_on'] = '';
                $p = $this->postJson("/api/v1/coordinator-imports/$id/preview", ['program_id' => $program->id, 'academic_term_id' => $term->id, 'rows' => $rows])->assertOk()->assertJsonPath('rows.0.errors', [])->json();
                $this->confirm($id, $p)->assertOk()->assertJsonPath('failed', 0);
            }
            $this->assertDatabaseCount('students', 1);
            $this->assertSame('Test Student', Student::sole()->user->name);
        } finally {
            @unlink($path);
            if (isset($book)) {
                $book->disconnectWorksheets();
            }
        }
    }

    public function test_new_accounts_are_pending_and_activated_only_through_an_emailed_link(): void
    {
        Notification::fake();
        [$actor, $program, $term] = $this->setupData();
        [$id, $missing] = $this->preview($actor, $program, $term, [$this->row(['email' => ''])]);
        $this->assertNotEmpty($missing['rows'][0]['errors']); // spec §17A-B: e-mail required for a new account
        $this->confirm($id, $missing)->assertUnprocessable();
        [$id, $preview] = $this->preview($actor, $program, $term, [$this->row(['academic_term' => $term->code])]);
        $this->assertSame([], $preview['rows'][0]['errors']);
        $this->confirm($id, $preview)->assertOk()->assertJsonPath('new_accounts', 1)->assertJsonPath('activation_links_sent', 1)
            ->assertJsonPath('rows.0.activation', 'link_sent');
        $user = Student::sole()->user;
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertFalse($user->must_change_password);
        $this->assertSame(486 * 60, StudentEnrollment::sole()->required_minutes);
        $this->assertDatabaseCount('import_credential_handoffs', 0); // nobody receives a password
        $this->postJson("/api/v1/coordinator-imports/$id/credentials")->assertStatus(410);
        $token = null;
        Notification::assertSentTo($user, AccountActivationLink::class, function ($n) use ($user, &$token) {
            parse_str((string) parse_url($n->toMail($user)->actionUrl, PHP_URL_QUERY), $q);
            $token = $q['token'] ?? null;

            return $token !== null;
        });
        $this->assertStringNotContainsString($token, (string) DB::table('excel_imports')->value('result'));
        $activation = fn () => collect($this->getJson('/api/v1/coordinator-imports/history')->assertOk()->json('data'))->firstWhere('id', $id)['result']['rows'][0]['activation'];
        $this->assertSame('link_sent', $activation());
        $this->getJson('/api/v1/enrollments')->assertJsonPath('data.0.student.user.status', 'pending')->assertJsonPath('data.0.student.user.activation', 'link_sent');
        $this->postJson('/api/v1/logout')->assertNoContent();
        $this->postJson('/api/v1/activation', ['token' => $token, 'password' => 'NewSecure!Password47', 'password_confirmation' => 'NewSecure!Password47'])->assertSuccessful();
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
        $this->postJson('/api/v1/login', ['email' => 'test.student@example.com', 'password' => 'NewSecure!Password47'])->assertOk();
        $this->postJson('/api/v1/logout')->assertNoContent();
        $this->actingAs($actor);
        $this->assertSame('activated', $activation());
    }

    public function test_reimport_and_new_term_preserve_password_and_closed_history(): void
    {
        [$actor, $program, $term] = $this->setupData();
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row(), $this->row()]);
        $this->assertSame(1, $p['counts']['duplicates']);
        $this->confirm($id, $p)->assertOk()->assertJsonPath('successful', 1)->assertJsonPath('skipped', 1);
        $password = Student::sole()->user->password;
        $old = StudentEnrollment::sole();
        $old->update(['status' => 'completed']);
        [$repeat, $p] = $this->preview($actor, $program, $term, [$this->row()]);
        $this->confirm($repeat, $p)->assertOk()->assertJsonPath('skipped', 1)->assertJsonPath('new_accounts', 0);
        $next = AcademicTerm::factory()->create();
        [$new, $p] = $this->preview($actor, $program, $next, [$this->row()]);
        $this->assertSame(1, $p['counts']['existing_accounts']);
        $this->confirm($new, $p)->assertOk()->assertJsonPath('updated', 1);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_enrollments', 2);
        $this->assertSame('completed', $old->fresh()->status->value);
        $this->assertSame($password, Student::sole()->user->password);
    }

    public function test_conflicts_invalid_ids_and_wrong_terms_block_confirmation(): void
    {
        [$actor, $program, $term] = $this->setupData();
        foreach ([['student_id' => '12345'], ['student_id' => '1234567'], ['email' => 'not-an-email'], ['academic_term' => 'Unknown term'], ['program' => 'BSCS'], ['last_name' => '']] as $change) {
            [$id, $p] = $this->preview($actor, $program, $term, [$this->row($change)]);
            $this->assertNotEmpty($p['rows'][0]['errors']);
            $this->confirm($id, $p)->assertUnprocessable();
        }
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row(), $this->row(['first_name' => 'Conflicting'])]);
        $this->assertNotEmpty($p['rows'][0]['errors']);
        $this->assertNotEmpty($p['rows'][1]['errors']);
        $this->confirm($id, $p)->assertUnprocessable();
        $this->assertDatabaseCount('students', 0);
    }

    public function test_only_missing_active_enrollment_facts_are_filled(): void
    {
        [$actor, $program, $term] = $this->setupData();
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row()]);
        $this->confirm($id, $p)->assertOk();
        $enrollment = StudentEnrollment::sole();
        $enrollment->update(['year_level' => null]);
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row(['enrolled_on' => '2026-08-01'])]);
        $this->assertSame(1, $p['counts']['enrollments_to_update']);
        $this->confirm($id, $p)->assertOk()->assertJsonPath('enrollments_updated', 1)->assertJsonPath('new_accounts', 0);
        $this->assertSame(4, $enrollment->fresh()->year_level);
        $enrollment->refresh()->update(['status' => 'withdrawn', 'year_level' => null]);
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row(['enrolled_on' => '2026-08-01'])]);
        $this->confirm($id, $p)->assertOk()->assertJsonPath('skipped', 1);
        $this->assertNull($enrollment->fresh()->year_level);
        $this->assertSame('withdrawn', $enrollment->fresh()->status->value);
    }

    public function test_deleted_accounts_are_not_duplicated_and_credentials_are_owner_only_and_expire(): void
    {
        [$actor, $program, $term] = $this->setupData();
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row()]);
        $this->confirm($id, $p)->assertOk();
        $other = User::factory()->withRole(Role::Coordinator)->create();
        $other->programs()->attach($program);
        $this->actingAs($other)->postJson("/api/v1/coordinator-imports/$id/credentials")->assertNotFound();
        $this->getJson('/api/v1/coordinator-imports/history')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($actor);
        $this->postJson("/api/v1/coordinator-imports/$id/credentials")->assertStatus(410);
        Student::sole()->user->delete();
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row()]);
        $this->assertNotEmpty($p['rows'][0]['errors']);
        $this->confirm($id, $p)->assertUnprocessable();
        $this->assertDatabaseCount('students', 1);
    }

    public function test_student_import_ignores_placement_fields_and_history_is_program_scoped(): void
    {
        [$actor, $program, $term] = $this->setupData();
        [$id, $p] = $this->preview($actor, $program, $term, [$this->row(['host_name' => 'Never Created', 'opportunity_title' => 'Never Created', 'starts_on' => '2026-08-01'])]);
        $this->confirm($id, $p)->assertOk()->assertJsonPath('failed', 0);
        $this->assertDatabaseCount('host_establishments', 0);
        $this->assertDatabaseCount('opportunities', 0);
        $this->assertDatabaseCount('placements', 0);
        $enrollment = StudentEnrollment::sole();
        $this->getJson("/api/v1/coordinator-students/$enrollment->id/history")->assertOk()->assertJsonCount(1, 'enrollments');
        $other = User::factory()->withRole(Role::Coordinator)->create();
        $this->actingAs($other)->getJson("/api/v1/coordinator-students/$enrollment->id/history")->assertNotFound();
        $this->actingAs($actor);
        $program->update(['is_active' => false]);
        $this->getJson("/api/v1/coordinator-students/$enrollment->id/history")->assertNotFound();
        $this->postJson("/api/v1/coordinator-imports/$id/credentials")->assertNotFound();
    }
}
