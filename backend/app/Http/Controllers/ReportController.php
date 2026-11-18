<?php

namespace App\Http\Controllers;

use App\Enums\{Permission, Role};
use App\Models\{GeneratedReport, ProgramTerm};
use App\Services\{Audit, ReportBuilder, ReportManagement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private function terms(Request $request)
    {
        return ReportManagement::terms($request->user());
    }

    private function reports(Request $request)
    {
        return ReportManagement::query($request->user());
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['program_term_id' => ['nullable', 'integer'], 'kind' => ['nullable', Rule::in(ReportBuilder::KINDS)]]);
        $reports = $this->reports($request)->when($filters['program_term_id'] ?? null, fn ($q, $id) => $q->where('program_term_id', $id))
            ->when($filters['kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))->latest('id')->paginate(20, ['id', 'program_term_id', 'kind', 'generated_by', 'approved_by', 'approved_at', 'created_at', 'content_hash']);

        return response()->json(['reports' => $reports, 'cohorts' => $this->terms($request)->with(['program:id,code', 'academicTerm:id,name'])->get(), 'kinds' => ReportBuilder::KINDS], headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request, ReportBuilder $builder)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $data = $request->validate(['program_term_id' => ['required', 'integer'], 'kind' => ['required', Rule::in(ReportBuilder::KINDS)]]);
        $report = ReportManagement::generate($request->user(), (int) $data['program_term_id'], $data['kind']);

        return response()->json(['data' => $report], 201);
    }

    public function show(Request $request, int $report)
    {
        return response()->json(['data' => $this->reports($request)->findOrFail($report)], headers: ['Cache-Control' => 'no-store']);
    }

    public function approve(Request $request, int $report)
    {
        $record = ReportManagement::approve($request->user(), $report);

        return response()->json(['data' => $record]);
    }

    public function download(Request $request, int $report)
    {
        $record = $this->reports($request)->findOrFail($report);
        $format = $request->validate(['format' => ['nullable', Rule::in(['csv', 'xlsx'])]])['format'] ?? 'csv';
        Audit::record($request->user(), 'report.exported', $record, ['format' => $format, 'content_hash' => $record->content_hash], $record->programTerm->program_id);
        if ($format === 'xlsx') {
            return $this->xlsx($request, $record);
        }

        return response()->streamDownload(function () use ($record) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $record->payload['columns'], escape: '');
            foreach ($record->payload['rows'] as $row) {
                // Prevent spreadsheet formula interpretation of user-supplied names/text.
                $safe = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($stream, $safe, escape: '');
            }
            fclose($stream);
        }, 'internmatch-'.$record->kind.'-'.$record->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /**
     * Spec §17A-C (FR-22): XLSX export of the same frozen, scope-checked report snapshot.
     * Text is written as explicit string cells, so spreadsheet software never evaluates it as a formula.
     */
    private function xlsx(Request $request, GeneratedReport $record)
    {
        $record->loadMissing('programTerm.program', 'programTerm.academicTerm');
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Report');
        $put = function ($sheet, int $col, int $row, $value): void {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$row;
            if (is_int($value) || is_float($value)) {
                $sheet->setCellValue($cell, $value);
            } else {
                $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        };
        foreach (array_values($record->payload['columns']) as $c => $column) {
            $put($sheet, $c + 1, 1, $column);
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        foreach ($record->payload['rows'] as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $put($sheet, $c + 1, $r + 2, $value);
            }
        }
        $info = $book->createSheet()->setTitle('Export details');
        $details = [
            ['Report', $record->kind],
            ['Program', $record->programTerm->program->code ?? ''],
            ['Academic term', $record->programTerm->academicTerm->name ?? ''],
            ['Report generated', (string) $record->created_at],
            ['Approval', $record->approved_at ? 'Approved '.$record->approved_at : 'Not approved'],
            ['Content hash', (string) $record->content_hash],
            ['Exported at', now()->toDateTimeString()],
            ['Exported by', $request->user()->name],
        ];
        foreach ($details as $i => [$label, $value]) {
            $put($info, 1, $i + 1, $label);
            $put($info, 2, $i + 1, $value);
        }
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($book) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'internmatch-'.$record->kind.'-'.$record->id.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store']);
    }
}
