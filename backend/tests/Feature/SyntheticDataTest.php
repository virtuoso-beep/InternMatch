<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{Placement, StudentEnrollment, TimeLog, User};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Synthetic test data is hidden by default, visible only on request, labelled when shown, and removable. */
class SyntheticDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array{0: StudentEnrollment, 1: StudentEnrollment, 2: User} real enrollment, synthetic enrollment, coordinator */
    private function cohort(): array
    {
        $real = StudentEnrollment::factory()->create();
        $synthetic = StudentEnrollment::factory()->create(['program_term_id' => $real->program_term_id, 'is_synthetic' => true]);
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($real->programTerm->program_id);

        return [$real, $synthetic, $coordinator];
    }

    private function stat(array $json, string $label): mixed
    {
        return collect($json['stats'])->firstWhere('label', $label)['value'];
    }

    public function test_dashboard_hides_synthetic_records_unless_included(): void
    {
        [, $synthetic, $coordinator] = $this->cohort();
        Placement::factory()->create(['student_enrollment_id' => $synthetic->id, 'status' => 'completed', 'completed_at' => now(), 'starts_on' => now()->subMonth(), 'supervisor_id' => User::factory()->withRole(Role::Supervisor)->create()->id]);
        $this->actingAs($coordinator);

        $hidden = $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('synthetic', ['can_include' => true, 'included' => false])->json();
        $this->assertSame(1, $this->stat($hidden, 'Enrollment records'));
        $this->assertSame(0, $this->stat($hidden, 'Completed placements'));

        $shown = $this->getJson('/api/v1/dashboard?include_synthetic=1')->assertOk()->assertJsonPath('synthetic.included', true)->json();
        $this->assertSame(2, $this->stat($shown, 'Enrollment records'));
        $this->assertSame(1, $this->stat($shown, 'Completed placements'));
    }

    public function test_program_analytics_hides_synthetic_records_unless_included(): void
    {
        [, , $coordinator] = $this->cohort();
        $this->actingAs($coordinator);
        $this->getJson('/api/v1/program-analytics')->assertOk()->assertJsonPath('programs.0.enrollments', 1)->assertJsonPath('synthetic.included', false);
        $this->getJson('/api/v1/program-analytics?include_synthetic=1')->assertOk()->assertJsonPath('programs.0.enrollments', 2);
    }

    public function test_reports_exclude_synthetic_by_default_and_label_it_when_included(): void
    {
        [$real, $synthetic, $coordinator] = $this->cohort();
        $this->actingAs($coordinator);

        $plain = $this->postJson('/api/v1/reports', ['program_term_id' => $real->program_term_id, 'kind' => 'progress'])->assertCreated();
        $plain->assertJsonPath('data.contains_synthetic', false)->assertJsonCount(1, 'data.payload.rows');
        $this->assertNotContains('synthetic_record', $plain->json('data.payload.columns'));

        $mixed = $this->postJson('/api/v1/reports', ['program_term_id' => $real->program_term_id, 'kind' => 'progress', 'include_synthetic' => true])->assertCreated();
        $mixed->assertJsonPath('data.contains_synthetic', true)->assertJsonCount(2, 'data.payload.rows');
        $this->assertSame('synthetic_record', last($mixed->json('data.payload.columns')));
        $flags = collect($mixed->json('data.payload.rows'))->mapWithKeys(fn ($row) => [$row[0] => last($row)]);
        $this->assertSame(['yes', 'no'], [$flags[$synthetic->id], $flags[$real->id]]);
        $this->getJson('/api/v1/reports?kind=progress')->assertJsonPath('reports.data.0.contains_synthetic', true);

        $id = $mixed->json('data.id');
        $csv = $this->get("/api/v1/reports/{$id}/download")->assertOk();
        $this->assertStringContainsString('contains-synthetic-data.csv', $csv->headers->get('Content-Disposition'));
        $this->assertStringContainsString('synthetic_record', $csv->streamedContent());
        $xlsx = $this->get("/api/v1/reports/{$id}/download?format=xlsx")->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'synthetic');
        try {
            file_put_contents($path, $xlsx);
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $details = collect($book->getSheet(1)->toArray())->mapWithKeys(fn ($row) => [$row[0] => $row[1]]);
            $this->assertStringStartsWith('Contains synthetic data', $details['Synthetic data']);
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
        $pdf = $this->get("/api/v1/reports/{$id}/download?format=pdf")->assertOk();
        $this->assertStringContainsString('contains-synthetic-data.pdf', $pdf->headers->get('Content-Disposition'));
        $html = view('reports.pdf', ['title' => 'T', 'notice' => 'Contains synthetic data: test', 'details' => [], 'columns' => ['a'], 'rows' => [['b']]])->render();
        $this->assertStringContainsString('Contains synthetic data: test', $html);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.generated', 'changes->contains_synthetic' => true]);
    }

    /** Text drawn by dompdf: content streams are Flate-compressed; strings are UTF-16BE inside TJ/Tj operators. */
    private function pdfText(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $text = [];
        foreach ($streams[1] as $stream) {
            $raw = @gzuncompress($stream);
            if ($raw === false || ! str_contains($raw, 'Tf')) {
                continue;
            }
            preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $raw, $strings);
            foreach ($strings[0] as $string) {
                $bytes = preg_replace_callback('/\\\\([0-7]{1,3}|.)/s', fn ($m) => ctype_digit($m[1]) ? chr(octdec($m[1])) : (['n' => "\n", 'r' => "\r", 't' => "\t"][$m[1]] ?? $m[1]), substr($string, 1, -1));
                $text[] = mb_convert_encoding($bytes, 'UTF-8', 'UTF-16BE');
            }
        }

        return preg_replace('/\s+/', ' ', implode(' ', $text));
    }

    public function test_pdf_download_prints_the_synthetic_banner_only_when_included(): void
    {
        [$real, $synthetic, $coordinator] = $this->cohort();
        $synthetic->student->user->update(['name' => 'Synthetic Pdf Student']);
        $this->actingAs($coordinator);
        $mixed = $this->postJson('/api/v1/reports', ['program_term_id' => $real->program_term_id, 'kind' => 'progress', 'include_synthetic' => true])->assertCreated()->json('data.id');
        $plain = $this->postJson('/api/v1/reports', ['program_term_id' => $real->program_term_id, 'kind' => 'progress'])->assertCreated()->json('data.id');

        $response = $this->get("/api/v1/reports/{$mixed}/download?format=pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString("internmatch-progress-{$mixed}-contains-synthetic-data.pdf", $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $text = $this->pdfText($response->getContent());
        $this->assertStringContainsString('Contains synthetic data: this report includes synthetic test records.', $text);
        $this->assertStringContainsString('Synthetic Pdf Student', $text);
        $this->assertStringContainsString('synthetic_record', $text);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'subject_id' => $mixed, 'changes->format' => 'pdf']);

        $clean = $this->get("/api/v1/reports/{$plain}/download?format=pdf")->assertOk();
        $this->assertStringNotContainsString('contains-synthetic', $clean->headers->get('Content-Disposition'));
        $cleanText = $this->pdfText($clean->getContent());
        $this->assertStringNotContainsString('Contains synthetic data', $cleanText);
        $this->assertStringNotContainsString('Synthetic Pdf Student', $cleanText);
    }

    public function test_batch_reports_hide_synthetic_by_default_and_purge_strips_synthetic_rows(): void
    {
        [$real, $synthetic, $coordinator] = $this->cohort();
        $this->actingAs($coordinator);
        $code = $real->programTerm->program->code;
        $row = fn ($e) => ['enrollment_id' => $e->id, 'status' => 'Skipped', 'reason' => 'test'];
        $mixed = $this->postJson('/api/v1/recommendation-batch-reports', ['program' => $code, 'results' => [$row($real), $row($synthetic)]])->assertCreated()->assertJsonPath('contains_synthetic', true)->json('id');
        $syntheticOnly = $this->postJson('/api/v1/recommendation-batch-reports', ['program' => $code, 'results' => [$row($synthetic)]])->assertCreated()->json('id');
        $realOnly = $this->postJson('/api/v1/recommendation-batch-reports', ['program' => $code, 'results' => [$row($real)]])->assertCreated()->assertJsonPath('contains_synthetic', false)->json('id');

        $this->getJson('/api/v1/recommendation-batch-reports')->assertOk()->assertJsonPath('synthetic.included', false)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $realOnly);
        $all = collect($this->getJson('/api/v1/recommendation-batch-reports?include_synthetic=1')->assertOk()->assertJsonCount(3, 'data')->json('data'))->keyBy('id');
        $this->assertTrue($all[$mixed]['contains_synthetic']);
        $this->assertSame([false, true], array_column($all[$mixed]['results'], 'synthetic'));

        $this->artisan('internmatch:purge-synthetic')->assertSuccessful();
        $this->assertDatabaseHas('recommendation_batch_reports', ['id' => $syntheticOnly]); // dry run
        $this->artisan('internmatch:purge-synthetic', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseMissing('recommendation_batch_reports', ['id' => $syntheticOnly]);
        $kept = DB::table('recommendation_batch_reports')->find($mixed);
        $this->assertSame([$real->id], array_column(json_decode($kept->results, true), 'enrollment_id'));
        $this->assertSame(0, (int) $kept->contains_synthetic);
        $this->assertDatabaseHas('recommendation_batch_reports', ['id' => $realOnly]);
    }

    public function test_training_export_separates_real_and_synthetic_labels(): void
    {
        Storage::fake('local');
        [$real, $synthetic, $coordinator] = $this->cohort();
        foreach ([[$real, 'suitable'], [$synthetic, 'unsuitable']] as [$enrollment, $judgment]) {
            $generation = (string) Str::uuid();
            $placement = Placement::factory()->create(['student_enrollment_id' => $enrollment->id]);
            $rec = DB::table('recommendations')->insertGetId(['generation_id' => $generation, 'student_enrollment_id' => $enrollment->id, 'opportunity_id' => $placement->opportunity_id, 'similarity_score' => .8, 'distance_km' => 2, 'capacity_at_time' => 3, 'moa_status_at_time' => 'active', 'rank' => 1, 'ranking_method' => 'cosine_then_distance', 'snapshot' => '{}', 'generated_at' => now()]);
            DB::table('placement_judgments')->insert(['recommendation_id' => $rec, 'coordinator_id' => $coordinator->id, 'judgment' => $judgment, 'reason' => 'test', 'approved_by' => $coordinator->id, 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $label = fn (?bool $suitable, string $source) => ['source_id' => $source, 'student_id' => $synthetic->student_id, 'program_id' => $synthetic->programTerm->program_id, 'hte_code' => 'HTE-101', 'opportunity_code' => 'OPP-101', 'program_match' => 1, 'skill_match_score' => .5, 'distance_km' => 3.2, 'available_slots' => 4, 'has_slot' => 1, 'moa_valid' => 1, 'coordinator_decision' => $suitable === null ? 'For Review' : 'Suitable', 'suitable' => $suitable, 'is_synthetic' => true];
        DB::table('coordinator_labels')->insert([$label(true, 'LBL-1'), $label(null, 'LBL-2')]);

        // Regression: the message must name the file actually written (local disk root), not storage/app/ml.
        $this->artisan('ml:export-judgments')->expectsOutputToContain(Storage::disk('local')->path('ml/judgment_export.csv'))->assertSuccessful();
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.local.root')); // ML default input assumes this root
        $rows = array_slice(explode("\n", trim(Storage::disk('local')->get('ml/judgment_export.csv'))), 1);
        $this->assertSame(['0.8,2,3,1,1,0'], $rows); // the real judgment only

        $this->artisan('ml:export-judgments', ['--source' => 'synthetic'])->assertSuccessful();
        $rows = array_slice(explode("\n", trim(Storage::disk('local')->get('ml/judgment_export.csv'))), 1);
        $this->assertSame(['0.5000,3.20,4,1,1,1'], $rows); // "For Review" (no label) is skipped; synthetic judgment not real

        $this->artisan('ml:export-judgments', ['--source' => 'all'])->assertSuccessful();
        $this->assertCount(2, array_slice(explode("\n", trim(Storage::disk('local')->get('ml/judgment_export.csv'))), 1));
        $this->artisan('ml:export-judgments', ['--source' => 'other'])->assertFailed();
    }

    public function test_purge_removes_only_synthetic_records_and_keeps_listed_accounts(): void
    {
        config(['internmatch.synthetic_keep_student_numbers' => ['150001']]);
        [$real, $synthetic, $coordinator] = $this->cohort();
        $kept = StudentEnrollment::factory()->create(['program_term_id' => $real->program_term_id, 'is_synthetic' => true]);
        foreach ([$synthetic, $kept] as $enrollment) {
            DB::table('students')->where('id', $enrollment->student_id)->update(['is_synthetic' => true]);
            DB::table('users')->where('id', $enrollment->student->user_id)->update(['is_synthetic' => true]);
        }
        DB::table('students')->where('id', $kept->student_id)->update(['student_number' => '150001']);
        $placement = Placement::factory()->create(['student_enrollment_id' => $kept->id]);
        TimeLog::factory()->create(['placement_id' => $placement->id]);
        DB::table('placements')->where('id', $placement->id)->update(['is_synthetic' => true]);
        $realPlacement = Placement::factory()->create(['student_enrollment_id' => $real->id]);

        $this->artisan('internmatch:purge-synthetic')->assertSuccessful();
        $this->assertDatabaseHas('student_enrollments', ['id' => $synthetic->id]); // dry run changed nothing

        $this->artisan('internmatch:purge-synthetic', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseMissing('student_enrollments', ['id' => $synthetic->id]);
        $this->assertDatabaseMissing('student_enrollments', ['id' => $kept->id]);
        $this->assertDatabaseMissing('placements', ['id' => $placement->id]);
        $this->assertDatabaseMissing('time_logs', ['placement_id' => $placement->id]);
        $this->assertDatabaseMissing('students', ['id' => $synthetic->student_id]);
        $this->assertDatabaseHas('students', ['id' => $kept->student_id, 'is_synthetic' => false]);
        $this->assertDatabaseHas('users', ['id' => $kept->student->user_id, 'is_synthetic' => false]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $real->id]);
        $this->assertDatabaseHas('placements', ['id' => $realPlacement->id]);
        $this->assertDatabaseHas('users', ['id' => $coordinator->id]);
        $this->assertDatabaseHas('program_terms', ['id' => $real->program_term_id]);
    }

    public function test_set_local_password_refuses_production_and_works_locally(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->app->detectEnvironment(fn () => 'production');
        $this->artisan('internmatch:set-local-password', ['email' => $user->email, '--generate' => true])->assertFailed();
        $before = $user->fresh()->password;
        $this->assertSame($before, $user->fresh()->password);

        $this->app->detectEnvironment(fn () => 'testing');
        $this->artisan('internmatch:set-local-password', ['email' => $user->email, '--generate' => true])->assertSuccessful();
        $this->assertNotSame($before, $user->fresh()->password);
        $this->artisan('internmatch:set-local-password', ['email' => 'nobody@example.com', '--generate' => true])->assertFailed();
    }

    public function test_set_local_password_for_all_synthetic_accounts_skips_kept_pending_and_real_accounts(): void
    {
        config(['internmatch.synthetic_keep_student_numbers' => ['150001']]);
        $real = User::factory()->withRole(Role::Coordinator)->create();
        [$synthetic, $kept, $pending] = [User::factory()->withRole(Role::Dean)->create(), User::factory()->withRole(Role::Student)->create(), User::factory()->withRole(Role::Student)->create(['status' => 'pending'])];
        DB::table('users')->whereIn('id', [$synthetic->id, $kept->id, $pending->id])->update(['is_synthetic' => true]);
        DB::table('students')->insert(['user_id' => $kept->id, 'student_number' => '150001', 'is_synthetic' => true, 'created_at' => now(), 'updated_at' => now()]);
        $before = DB::table('users')->pluck('password', 'id');

        $this->app->detectEnvironment(fn () => 'production');
        $this->artisan('internmatch:set-local-password', ['--all-synthetic' => true, '--generate' => true])->assertFailed();
        $this->assertEquals($before, DB::table('users')->pluck('password', 'id'));

        $this->app->detectEnvironment(fn () => 'testing');
        $this->artisan('internmatch:set-local-password', ['--all-synthetic' => true, '--generate' => true])->expectsOutputToContain('1 synthetic accounts')->assertSuccessful();
        $after = DB::table('users')->pluck('password', 'id');
        $this->assertNotSame($before[$synthetic->id], $after[$synthetic->id]);
        foreach ([$real, $kept, $pending] as $untouched) {
            $this->assertSame($before[$untouched->id], $after[$untouched->id]);
        }
        $this->artisan('internmatch:set-local-password', ['email' => $real->email, '--all-synthetic' => true])->assertFailed(); // one target only
    }
}
