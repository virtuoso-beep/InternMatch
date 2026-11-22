<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Opportunity;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountActivation;
use App\Services\CoordinatorExcelImport;
use App\Services\CoordinatorWorkbook;
use App\Services\HostAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CoordinatorImportController extends Controller
{
    public function template(Request $request)
    {
        $this->authorizeCoordinator($request);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Students');
        $sheet->fromArray([['Student ID', 'First Name', 'Middle Name', 'Last Name', 'Program', 'Academic Term', 'Year Level', 'Email']]);
        $sheet->getStyle('A2:A1001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'InternMatch-Student-Import.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store']);
    }

    public function history(Request $request)
    {
        $this->authorizeCoordinator($request);
        $programIds = ($request->user()->role === Role::Admin ? Program::query() : $request->user()->programs())->where('is_active', true)->pluck('programs.id')->all();
        $batches = DB::table('excel_imports')->where('user_id', $request->user()->id)->where('module', 'student')
            ->where(fn ($q) => $q->whereNull('preview')->orWhereIn('preview->program_id', $programIds))
            ->orderByDesc('created_at')->paginate(20, ['id', 'filename', 'created_at', 'updated_at', 'result']);
        $batches->through(function ($batch) {
            $batch->result = $batch->result ? json_decode($batch->result, true) : null;
            // Live activation state (link sent / activated / expired); "email failed" is kept from the import.
            $numbers = collect($batch->result['rows'] ?? [])->filter(fn ($row) => isset($row['activation']))->pluck('student_id')->all();
            if ($numbers) {
                $users = Student::whereIn('student_number', $numbers)->pluck('user_id', 'student_number');
                $states = AccountActivation::statusFor($users->values()->all());
                foreach ($batch->result['rows'] as &$row) {
                    if (! isset($row['activation'])) {
                        continue;
                    }
                    $live = $states[$users[$row['student_id']] ?? 0] ?? null;
                    // A failed e-mail stays visible until the student activates (a re-sent link shows as sent).
                    if ($live === 'activated' || $row['activation'] !== 'email_failed') {
                        $row['activation'] = $live ?? $row['activation'];
                    }
                }
                unset($row);
            }
            $batch->credentials_available = DB::table('import_credential_handoffs')->where('import_id', $batch->id)->whereNull('consumed_at')->where('expires_at', '>', now())->exists();

            return $batch;
        });

        return response()->json($batches, headers: ['Cache-Control' => 'no-store']);
    }

    public function credentials(Request $request, string $id)
    {
        $batch = $this->batch($request, $id);
        $preview = json_decode($batch->preview, true);
        CoordinatorExcelImport::authorize($request->user(), $preview['program_id']);
        $credentials = DB::transaction(function () use ($request, $id) {
            $handoff = DB::table('import_credential_handoffs')->where('import_id', $id)->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($handoff && ! $handoff->consumed_at && now()->lt($handoff->expires_at), 410, 'Credential download has expired or already been used. Existing passwords have not been reset.');
            $value = json_decode(Crypt::decryptString($handoff->encrypted_credentials), true);
            DB::table('import_credential_handoffs')->where('import_id', $id)->update(['encrypted_credentials' => null, 'consumed_at' => now()]);

            return $value;
        });
        // A text file preserves leading zeros and is not executable as spreadsheet formulas.
        $content = "Student ID\tTemporary password\r\n";
        foreach ($credentials as $credential) {
            $content .= $credential['student_id']."\t".$credential['temporary_password']."\r\n";
        }

        return response()->json(['content' => $content], headers: ['Cache-Control' => 'no-store, private', 'Pragma' => 'no-cache']);
    }

    private function authorizeCoordinator(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [Role::Coordinator, Role::Admin], true) && $request->user()->status === AccountStatus::Active, 403);
    }

    private function batch(Request $request, string $id)
    {
        $this->authorizeCoordinator($request);
        $batch = DB::table('excel_imports')->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($batch, 404);

        return $batch;
    }

    public function reference(Request $request)
    {
        $this->authorizeCoordinator($request);
        $ids = ($request->user()->role === Role::Admin ? Program::query() : $request->user()->programs())->where('is_active', true)->pluck('programs.id');

        return response()->json([
            'programs' => Program::whereIn('id', $ids)->get(['id', 'code', 'name', 'required_ojt_hours']),
            'terms' => AcademicTerm::orderByDesc('starts_on')->get(['id', 'code', 'name', 'starts_on', 'ends_on']),
            'hosts' => HostAccess::query($request->user())->orderBy('name')->get(['id', 'name', 'address', 'city']),
            'opportunities' => Opportunity::whereHas('programs', fn ($q) => $q->whereIn('programs.id', $ids))->with('programs:id,code')->get(['id', 'title', 'host_establishment_id', 'academic_term_id', 'status']),
            'fields' => array_keys(CoordinatorWorkbook::ALIASES),
        ], headers: ['Cache-Control' => 'no-store']);
    }

    public function upload(Request $request)
    {
        $this->authorizeCoordinator($request);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], 'module' => 'sometimes|in:student,host,opportunity,agreement']);
        abort_if($request->user()->role === Role::Admin && $request->input('module', 'student') !== 'student', 403);
        $file = $request->file('file');
        $sheets = CoordinatorWorkbook::read($file->getRealPath());
        $id = (string) Str::uuid();
        DB::table('excel_imports')->insert(['id' => $id, 'user_id' => $request->user()->id, 'filename' => basename($file->getClientOriginalName()),
            'module' => $request->input('module', 'student'), 'workbook' => json_encode($sheets, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['id' => $id, 'sheets' => $sheets], 201, ['Cache-Control' => 'no-store']);
    }

    public function map(Request $request, string $id)
    {
        $batch = $this->batch($request, $id);
        abort_if($batch->result, 409, 'This import has already been confirmed. Upload another workbook to start a new import.');
        $data = $request->validate(['sheet' => 'required|integer|min:0', 'mapping' => 'present|array', 'mapping.*' => 'nullable|integer|between:0,99']);
        $sheets = json_decode($batch->workbook, true);
        abort_unless(isset($sheets[$data['sheet']]), 422, 'Unknown sheet.');
        abort_if(array_diff(array_keys($data['mapping']), array_keys(CoordinatorWorkbook::ALIASES)) !== [], 422, 'Unknown mapped field.');
        $rows = CoordinatorWorkbook::rows($sheets, $data['sheet'], $data['mapping']);
        foreach ($rows as &$row) {
            $row['kind'] = $batch->module;
        }

        return response()->json(['rows' => $rows], headers: ['Cache-Control' => 'no-store']);
    }

    public function preview(Request $request, string $id)
    {
        $batch = $this->batch($request, $id);
        abort_if($batch->result, 409, 'Import already confirmed.');
        $data = $request->validate(['program_id' => 'required|integer', 'academic_term_id' => ($batch->module === 'agreement' ? 'nullable' : 'required').'|integer|exists:academic_terms,id',
            'rows' => 'required|array|min:1|max:1000', 'rows.*' => 'required|array', 'rows.*.*' => 'nullable']);
        foreach ($data['rows'] as $row) {
            foreach ($row as $value) {
                abort_if(is_array($value) || is_object($value) || (is_string($value) && strlen($value) > 10000), 422, 'Invalid row value.');
            }
        }
        foreach ($data['rows'] as $row) {
            abort_unless(($row['kind'] ?? '') === $batch->module, 422, 'Record type must match the module where you uploaded this workbook.');
        }
        $preview = CoordinatorExcelImport::inspect($request->user(), $data);
        $revision = Str::random(64);
        DB::table('excel_imports')->where('id', $id)->whereNull('result')->update(['preview' => json_encode($preview, JSON_THROW_ON_ERROR), 'revision' => $revision, 'updated_at' => now()]);

        return response()->json($preview + ['revision' => $revision], headers: ['Cache-Control' => 'no-store']);
    }

    public function confirm(Request $request, string $id)
    {
        $this->batch($request, $id);
        $data = $request->validate(['revision' => 'required|string|size:64']);
        // Serialize imports across programs: Student IDs and HTE identity are institution-wide.
        $lock = DB::selectOne("SELECT GET_LOCK('internmatch_excel_import', 10) AS acquired");
        abort_unless((int) $lock->acquired === 1, 409, 'Another import is saving. Please retry.');
        $toActivate = [];
        try {
            $result = DB::transaction(function () use ($request, $id, $data, &$toActivate) {
                $batch = DB::table('excel_imports')->where('id', $id)->where('user_id', $request->user()->id)->lockForUpdate()->first();
                abort_unless($batch && $batch->revision === $data['revision'], 409, 'Preview changed. Validate again before confirming.');
                $preview = json_decode($batch->preview, true);
                CoordinatorExcelImport::authorize($request->user(), $preview['program_id']);
                if ($batch->result) {
                    return json_decode($batch->result, true);
                }
                $fresh = CoordinatorExcelImport::inspect($request->user(), ['program_id' => $preview['program_id'], 'academic_term_id' => $preview['academic_term_id'], 'rows' => array_column($preview['rows'], 'row')]);
                abort_if(collect($fresh['rows'])->contains(fn ($entry) => count($entry['errors']) > 0), 422, 'There are unresolved or newly changed conflicts. Validate again and correct or skip those rows.');
                $result = ['existing_accounts' => 0, 'enrollments_created' => 0, 'enrollments_updated' => 0, 'successful' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'rows' => []];
                $fresh['import_id'] = $id;
                $credentials = [];
                foreach ($fresh['rows'] as $entry) {
                    $status = 'skipped';
                    $message = 'Skipped by coordinator.';
                    if (! ($entry['row']['skip'] ?? false)) {
                        try {
                            $rowCredentials = [];
                            $current = null;
                            $status = DB::transaction(function () use ($request, $fresh, $entry, &$rowCredentials, &$current) {
                                $current = CoordinatorExcelImport::inspect($request->user(), ['program_id' => $fresh['program_id'], 'academic_term_id' => $fresh['academic_term_id'], 'rows' => [$entry['row']]])['rows'][0];
                                if ($current['errors']) {
                                    throw new \RuntimeException(implode(' ', $current['errors']));
                                }

                                return CoordinatorExcelImport::saveRow($request->user(), $fresh, $current, $rowCredentials);
                            });
                            if ($entry['row']['kind'] === 'student') {
                                if (isset($entry['matched']['student_id']) || isset($entry['matched']['user_id'])) {
                                    $result['existing_accounts']++;
                                }
                                if (! isset($current['matched']['enrollment_id']) && $status !== 'skipped') {
                                    $result['enrollments_created']++;
                                }
                                if (isset($current['matched']['enrollment_id']) && $status === 'updated') {
                                    $result['enrollments_updated']++;
                                }
                            }
                            $credentials = array_merge($credentials, $rowCredentials);
                            $message = match ($status) {
                                'successful' => 'New records saved.', 'updated' => 'Existing identity matched; new links or explicitly requested missing fields saved.', default => 'Already exists; no duplicate created.'
                            };
                        } catch (\Throwable $error) {
                            $status = 'failed';
                            $message = $error instanceof QueryException ? 'Database conflict. No changes from this row were saved. Refresh and review the existing record.' : $error->getMessage();
                        }
                    }
                    $result[$status]++;
                    $result['rows'][] = ['source' => $entry['row']['source'], 'student_id' => $entry['row']['student_id'] ?? null, 'status' => $status, 'message' => $message];
                }
                // Spec §17A-B: no passwords are handed to anyone; links are e-mailed after the import commits.
                $toActivate = $credentials;
                $result['new_accounts'] = count($credentials);
                DB::table('excel_imports')->where('id', $id)->update(['result' => json_encode($result, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
                DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'program_id' => $preview['program_id'], 'action' => 'coordinator.excel_imported',
                    'subject_type' => 'user', 'subject_id' => $request->user()->id, 'changes' => json_encode(['import_id' => $id, 'counts' => array_diff_key($result, ['rows' => true])]), 'created_at' => now()]);

                return $result;
            });
        } finally {
            DB::select("SELECT RELEASE_LOCK('internmatch_excel_import')");
        }
        if ($toActivate) {
            $sent = [];
            foreach ($toActivate as $item) {
                try {
                    AccountActivation::issue($request->user(), User::findOrFail($item['activate_user_id']));
                    $sent[$item['student_id']] = 'link_sent';
                } catch (\Throwable $error) {
                    report($error);
                    $sent[$item['student_id']] = 'email_failed';
                }
            }
            foreach ($result['rows'] as &$row) {
                if (isset($sent[$row['student_id'] ?? ''])) {
                    $row['activation'] = $sent[$row['student_id']];
                }
            }
            unset($row);
            $result['activation_links_sent'] = count(array_filter($sent, fn ($v) => $v === 'link_sent'));
            $result['activation_email_failed'] = count($sent) - $result['activation_links_sent'];
            DB::table('excel_imports')->where('id', $id)->update(['result' => json_encode($result, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        }

        return response()->json($result, headers: ['Cache-Control' => 'no-store']);
    }
}
