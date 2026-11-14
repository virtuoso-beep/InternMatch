<?php

namespace Tests\Feature;

use App\Enums\{AccountStatus, Role};
use App\Models\{AcademicTerm, HostEstablishment, Opportunity, Placement, Program, Student, StudentEnrollment, User};
use App\Services\CoordinatorWorkbook;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};
use Tests\TestCase;

class CoordinatorExcelImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function setupImport(): array
    {
        $program = Program::factory()->create(['code' => 'BSIT', 'required_ojt_hours' => 486]);
        $term = AcademicTerm::factory()->create();
        $actor = User::factory()->withRole(Role::Coordinator)->create();
        $actor->programs()->attach($program);
        $this->actingAs($actor);
        return [$actor, $program, $term];
    }

    private function batch(User $actor, string $module = 'student'): string
    {
        $id = (string) Str::uuid();
        DB::table('excel_imports')->insert(['id' => $id, 'user_id' => $actor->id, 'module' => $module, 'filename' => 'test.xlsx', 'workbook' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        return $id;
    }

    private function row(string $studentId = '001234', string $email = 'import.student@example.test'): array
    {
        return ['kind' => 'student', 'student_id' => $studentId, 'name' => 'Same Student Name', 'email' => $email, 'program' => 'BSIT',
            'year_level' => '4', 'enrolled_on' => '2026-08-01', 'host_name' => 'TEST Accounting and IT Host', 'host_address' => 'Test Street', 'city' => 'Tagum City',
            'opportunity_title' => 'TEST IT Internship', 'tasks' => 'Test application support', 'capacity' => '4', 'host_capacity' => '4', 'starts_on' => '2026-08-10', 'placement_status' => 'Pending'];
    }

    private function preview(string $id, Program $program, AcademicTerm $term, array $rows)
    {
        return $this->postJson("/api/v1/coordinator-imports/{$id}/preview", ['program_id' => $program->id, 'academic_term_id' => $term->id, 'rows' => $rows]);
    }

    public function test_student_import_is_idempotent_and_never_populates_placements(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        $id = $this->batch($actor);
        $preview = $this->preview($id, $program, $term, [$this->row(), $this->row()])->assertOk()->assertJsonPath('rows.0.errors', [])->json();
        $result = $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $preview['revision']])->assertOk();
        $result->assertJsonPath('successful', 1)->assertJsonPath('skipped', 1)->assertJsonPath('failed', 0);
        $this->assertDatabaseCount('students', 1); $this->assertDatabaseCount('student_enrollments', 1);
        $this->assertDatabaseCount('placements', 0); $this->assertDatabaseCount('host_establishments', 0); $this->assertDatabaseCount('opportunities', 0);
        $this->assertSame(486 * 60, StudentEnrollment::sole()->required_minutes);
        $this->assertSame(AccountStatus::Active, Student::sole()->user->status);
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $preview['revision']])->assertOk()->assertExactJson($result->json());
        $second = $this->batch($actor);
        $next = $this->preview($second, $program, $term, [$this->row()])->assertOk()->assertJsonPath('rows.0.errors', [])->json();
        $this->postJson("/api/v1/coordinator-imports/{$second}/confirm", ['revision' => $next['revision']])->assertOk()->assertJsonPath('skipped', 1);
    }

    public function test_same_name_different_ids_are_separate_and_existing_password_is_preserved(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        $user = User::factory()->withRole(Role::Student)->create(['name' => 'Same Student Name', 'email' => 'import.student@example.test']);
        $password = $user->password;
        Student::create(['user_id' => $user->id, 'student_number' => '001234']);
        $id = $this->batch($actor);
        $p = $this->preview($id, $program, $term, [$this->row(), $this->row('001235', 'different@example.test')])->assertOk()->json();
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertOk()->assertJsonPath('failed', 0);
        $this->assertDatabaseCount('students', 2); $this->assertDatabaseCount('placements', 0); $this->assertDatabaseCount('host_establishments', 0);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_conflicts_are_previewed_and_skipped_rows_never_write(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        $id = $this->batch($actor);
        $conflict = $this->row(); $conflict['name'] = 'Different identity';
        $missing = $this->row(); unset($missing['student_id']);
        $p = $this->preview($id, $program, $term, [$this->row(), $conflict, $missing])->assertOk()->json();
        $this->assertNotEmpty($p['rows'][0]['errors']); $this->assertNotEmpty($p['rows'][1]['errors']); $this->assertNotEmpty($p['rows'][2]['errors']);
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertUnprocessable();
        $this->assertDatabaseCount('students', 0);
        $p = $this->preview($id, $program, $term, [$this->row(), $conflict + ['skip' => true], $missing + ['skip' => true]])->assertOk()->json();
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertOk()->assertJsonPath('skipped', 2)->assertJsonPath('successful', 1);
    }

    public function test_permissions_owner_revision_and_assignment_are_rechecked(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        $id = $this->batch($actor);
        $p = $this->preview($id, $program, $term, [$this->row()])->assertOk()->json();
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => str_repeat('x', 64)])->assertConflict();
        $otherProgram = Program::factory()->create(['code' => 'BSCS']);
        $this->preview($id, $otherProgram, $term, [$this->row()])->assertNotFound();
        $other = User::factory()->withRole(Role::Coordinator)->create(); $other->programs()->attach($program);
        $this->actingAs($other)->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertNotFound();
        foreach ([Role::Student, Role::Admin, Role::Dean, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->getJson('/api/v1/coordinator-imports/reference')->assertForbidden();
        }
        $actor->programs()->detach();
        $this->actingAs($actor)->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertNotFound();
        $this->assertDatabaseCount('students', 0);
    }

    public function test_invalid_values_and_email_and_placement_conflicts_are_reported_before_saving(): void
    {
        [$actor, $program, $term] = $this->setupImport(); $id = $this->batch($actor);
        $bad = $this->row(); $bad['enrolled_on'] = 'not-a-date'; $bad['year_level'] = '91';
        $this->assertNotEmpty($this->preview($id, $program, $term, [$bad])->assertOk()->json('rows.0.errors'));
        $p = $this->preview($id, $program, $term, [$this->row(), $this->row('DIFFERENT-ID')])->assertOk()->json();
        $this->assertNotEmpty($p['rows'][0]['errors']); $this->assertNotEmpty($p['rows'][1]['errors']);
        $differentPlacement = $this->row(); $differentPlacement['year_level'] = '3';
        $p = $this->preview($id, $program, $term, [$this->row(), $differentPlacement])->assertOk()->json();
        $this->assertNotEmpty($p['rows'][0]['errors']); $this->assertNotEmpty($p['rows'][1]['errors']);
        $this->assertDatabaseCount('students', 0);
    }

    public function test_xlsx_and_xls_upload_mapping_preserve_student_ids_dates_and_do_not_execute_formulas(): void
    {
        $this->setupImport();
        foreach (['Xlsx' => 'xlsx', 'Xls' => 'xls'] as $format => $extension) {
            $book = new Spreadsheet; $sheet = $book->getActiveSheet();
            $sheet->fromArray([['Practicum data'], ['Student No.', 'Full Name', 'School Email', 'Deployment Date'], ['001234', 'Test Name', 'test@example.test', 46244], ['001235', '=1+1', 'two@example.test', 46245]]);
            $sheet->getStyle('A3:A4')->getNumberFormat()->setFormatCode('000000');
            $sheet->getStyle('D3:D4')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $path = tempnam(sys_get_temp_dir(), 'excel'); IOFactory::createWriter($book, $format)->save($path);
            try {
                $response = $this->post('/api/v1/coordinator-imports', ['file' => new UploadedFile($path, 'test.'.$extension, null, null, true)], ['Accept' => 'application/json'])->assertCreated()->json();
                $mapping = $response['sheets'][0]['mapping'];
                $mapped = $this->postJson('/api/v1/coordinator-imports/'.$response['id'].'/map', ['sheet' => 0, 'mapping' => $mapping])->assertOk()->json('rows');
                $this->assertSame('001234', $mapped[0]['student_id']);
                $this->assertMatchesRegularExpression('/^2026-\d{2}-\d{2}$/', $mapped[0]['starts_on']);
                $this->assertStringContainsString('[Formula:', $mapped[1]['name']);
            } finally { @unlink($path); $book->disconnectWorksheets(); }
        }
    }

    public function test_multisheet_reference_joins_only_unambiguous_identifiers(): void
    {
        $sheets = [
            ['name' => 'Students Records', 'mapping' => ['student_id' => 0, 'name' => 1, 'program' => 2], 'rows' => [['source_row' => 2, 'cells' => ['001','Real Name','BSA']]]],
            ['name' => 'HTE Records', 'mapping' => ['host_code' => 0, 'host_name' => 1], 'rows' => [['source_row' => 2, 'cells' => ['HTE-1','Real HTE']]]],
            ['name' => 'Placement Records', 'mapping' => ['student_id' => 0, 'host_code' => 1], 'rows' => [['source_row' => 2, 'cells' => ['001','HTE-1']]]],
        ];
        $rows = CoordinatorWorkbook::rows($sheets, 2, $sheets[2]['mapping']);
        $this->assertSame('Real Name', $rows[0]['name']); $this->assertSame('Real HTE', $rows[0]['host_name']);
        $sheets[0]['rows'][] = ['source_row' => 3, 'cells' => ['001','Another Name','BSA']];
        $rows = CoordinatorWorkbook::rows($sheets, 2, $sheets[2]['mapping']);
        $this->assertArrayNotHasKey('name', $rows[0]); $this->assertArrayHasKey('reference_warning', $rows[0]);
    }

    public function test_each_module_imports_its_own_records_and_agreements_need_no_term(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        foreach (['host', 'opportunity', 'agreement'] as $module) {
            $id = $this->batch($actor, $module);
            $row = ['kind' => $module, 'host_name' => 'Module Host', 'host_address' => 'Confirmed Street', 'city' => 'Tagum',
                'opportunity_title' => 'Confirmed internship', 'tasks' => 'Application support', 'capacity' => '2',
                'reference_number' => 'REAL-REF-001', 'effective_on' => '2026-01-01', 'expires_on' => '2026-12-31'];
            if ($module !== 'agreement') $row['host_capacity'] = '2';
            $input = ['program_id' => $program->id, 'academic_term_id' => $module === 'agreement' ? null : $term->id, 'rows' => [$row, $row]];
            $p = $this->postJson("/api/v1/coordinator-imports/{$id}/preview", $input)->assertOk()->assertJsonPath('rows.0.errors', [])->json();
            $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertOk()->assertJsonPath('successful', 1)->assertJsonPath('skipped', 1)->assertJsonPath('failed', 0);
            $next = $this->batch($actor, $module);
            $p = $this->postJson("/api/v1/coordinator-imports/{$next}/preview", $input)->assertOk()->json();
            $this->postJson("/api/v1/coordinator-imports/{$next}/confirm", ['revision' => $p['revision']])->assertOk()->assertJsonPath('skipped', 2);
        }
        $this->assertDatabaseCount('students', 0); $this->assertDatabaseCount('placements', 0);
        $this->assertDatabaseCount('host_establishments', 1); $this->assertDatabaseCount('opportunities', 1); $this->assertDatabaseCount('moas', 1);
        $this->assertSame('draft', \App\Models\Moa::sole()->status->value);
        $this->assertSame('draft', Opportunity::sole()->status->value);
        $this->getJson('/api/v1/moas')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/opportunities')->assertOk()->assertJsonCount(1, 'data');
        $this->preview($id, $program, $term, [$this->row()])->assertConflict();
        $this->preview($this->batch($actor, 'host'), $program, $term, [$this->row()])->assertUnprocessable();
    }

    public function test_import_does_not_create_approval_proposals(): void
    {
        [$actor, $program, $term] = $this->setupImport(); $id = $this->batch($actor);
        $p = $this->preview($id, $program, $term, [$this->row()])->assertOk()->json();
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $p['revision']])->assertOk()->assertJsonPath('failed', 0);
        $this->assertDatabaseCount('placements', 0);
        $this->assertDatabaseCount('placement_proposals', 0);
        $this->assertDatabaseCount('placement_proposal_items', 0);
    }

    public function test_formatted_blank_rows_in_multisheet_templates_are_not_data_rows(): void
    {
        $book = new Spreadsheet;
        for ($i = 0; $i < 12; $i++) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->fromArray([['Student ID', 'Student Name'], ['0001', 'Confirmed Name']]);
            $sheet->getStyle('A1000:B1000')->getFont()->setBold(true);
        }
        $path = tempnam(sys_get_temp_dir(), 'excel'); IOFactory::createWriter($book, 'Xlsx')->save($path);
        try {
            $sheets = CoordinatorWorkbook::read($path);
            $this->assertCount(12, $sheets);
            foreach ($sheets as $sheet) $this->assertCount(1, $sheet['rows']);
        } finally { @unlink($path); $book->disconnectWorksheets(); }
    }

    public function test_complete_uploaded_workbook_flow_saves_and_reimports_without_duplicates(): void
    {
        [$actor, $program, $term] = $this->setupImport();
        foreach (['Xlsx' => 'xlsx', 'Xls' => 'xls'] as $format => $extension) {
            $book = new Spreadsheet;
            $first = ['001234', 'Same Name', 'first@example.test', 'BSIT', '4', '2026-08-01', 'Upload Host', 'Confirmed Street', 'Tagum', 'IT Internship', 'Application support', '4', '4', '2026-08-10'];
            $second = $first; $second[0] = '001235'; $second[2] = 'second@example.test';
            $book->getActiveSheet()->fromArray([
                ['Student No.', 'Full Name', 'School Email', 'Course', 'Year Level', 'Enrollment Date', 'HTE Name', 'HTE Address', 'City', 'Opportunity Title', 'Duties', 'Available Slots', 'HTE Capacity', 'Deployment Date'],
                $first, $first, $second,
            ]);
            $book->getActiveSheet()->getStyle('A2:A4')->getNumberFormat()->setFormatCode('000000');
            $path = tempnam(sys_get_temp_dir(), 'excel'); IOFactory::createWriter($book, $format)->save($path);
            try {
                $upload = $this->post('/api/v1/coordinator-imports', ['module' => 'student', 'file' => new UploadedFile($path, 'flow.'.$extension, null, null, true)], ['Accept' => 'application/json'])->assertCreated()->json();
                $id = $upload['id'];
                $rows = $this->postJson("/api/v1/coordinator-imports/{$id}/map", ['sheet' => 0, 'mapping' => $upload['sheets'][0]['mapping']])->assertOk()->json('rows');
                $preview = $this->preview($id, $program, $term, $rows)->assertOk()->assertJsonPath('rows.0.errors', [])->assertJsonPath('rows.2.errors', [])->json();
                $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $preview['revision']])->assertOk()
                    ->assertJsonPath('successful', $format === 'Xlsx' ? 2 : 0)->assertJsonPath('skipped', $format === 'Xlsx' ? 1 : 3)->assertJsonPath('failed', 0);
                $this->assertDatabaseCount('students', 2); $this->assertDatabaseCount('student_enrollments', 2);
                $this->assertDatabaseCount('host_establishments', 0); $this->assertDatabaseCount('opportunities', 0); $this->assertDatabaseCount('placements', 0);
                $this->assertDatabaseHas('students', ['student_number' => '001234']);
            } finally { @unlink($path); $book->disconnectWorksheets(); }
        }
    }

    public function test_repeated_shared_host_conflicts_are_reported_before_any_write(): void
    {
        [$actor, $program, $term] = $this->setupImport(); $id = $this->batch($actor, 'host');
        $first = $this->row(); $first['kind'] = 'host'; unset($first['student_id']);
        $second = $first; $second['host_address'] = 'Different address';
        $preview = $this->preview($id, $program, $term, [$first, $second])->assertOk()->json();
        $this->assertNotEmpty($preview['rows'][0]['errors']); $this->assertNotEmpty($preview['rows'][1]['errors']);
        $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $preview['revision']])->assertUnprocessable();
        $this->assertDatabaseCount('students', 0); $this->assertDatabaseCount('host_establishments', 0);
    }

    public function test_actual_coordinator_workbook_requires_review_without_creating_live_identities(): void
    {
        $path = getenv('COORDINATOR_REFERENCE_WORKBOOK');
        if (! $path || ! is_file($path)) $this->markTestSkipped('Set COORDINATOR_REFERENCE_WORKBOOK to run the private source workbook check.');
        [$actor, $program, $term] = $this->setupImport();
        $program->update(['code' => 'BSA', 'required_ojt_hours' => 400]);
        foreach (['student' => 'Students Records', 'host' => 'HTE Records', 'opportunity' => 'HTE Opportunities', 'agreement' => 'HTE Records'] as $module => $sheetName) {
            $upload = $this->post('/api/v1/coordinator-imports', ['module' => $module, 'file' => new UploadedFile($path, 'coordinator-source.xlsx', null, null, true)], ['Accept' => 'application/json'])->assertCreated()->json();
            $this->assertCount(12, $upload['sheets']);
            $index = array_search($sheetName, array_column($upload['sheets'], 'name'), true);
            $this->assertNotFalse($index);
            $id = $upload['id'];
            $rows = $this->postJson("/api/v1/coordinator-imports/{$id}/map", ['sheet' => $index, 'mapping' => $upload['sheets'][$index]['mapping']])->assertOk()->json('rows');
            $this->assertCount(10, $rows);
            $preview = $this->preview($id, $program, $term, $rows)->assertOk()->json();
            $this->assertNotEmpty($preview['rows'][0]['errors']);
            $this->postJson("/api/v1/coordinator-imports/{$id}/confirm", ['revision' => $preview['revision']])->assertUnprocessable();
            $this->assertDatabaseCount('students', 0); $this->assertDatabaseCount('host_establishments', 0);
            $this->assertDatabaseCount('opportunities', 0); $this->assertDatabaseCount('moas', 0);
        }
    }
}
