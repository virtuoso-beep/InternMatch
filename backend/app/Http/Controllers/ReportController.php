<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\GeneratedReport;
use App\Services\Audit;
use App\Services\ReportBuilder;
use App\Services\ReportManagement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public const SYNTHETIC_LABEL = 'Contains test data';

    /** Columns and rows as shown and exported. Reports saved before 2026-11-22 used "synthetic" wording. */
    public static function columns(GeneratedReport $record): array
    {
        return array_map(fn ($column) => $column === 'synthetic_record' ? 'test_record' : $column, array_values($record->payload['columns']));
    }

    public static function rows(GeneratedReport $record): array
    {
        return array_map(fn ($row) => array_map(fn ($value) => $value === 'includes synthetic' ? 'includes test data' : $value, array_values($row)), $record->payload['rows']);
    }

    private static function present(GeneratedReport $record): GeneratedReport
    {
        $record->setAttribute('payload', ['columns' => self::columns($record), 'rows' => self::rows($record)] + $record->payload);

        return $record;
    }

    private function filename(GeneratedReport $record, string $extension): string
    {
        return 'internmatch-'.$record->kind.'-'.$record->id.($record->contains_synthetic ? '-contains-test-data' : '').'.'.$extension;
    }

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
            ->when($filters['kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))->latest('id')->paginate(20, ['id', 'program_term_id', 'kind', 'contains_synthetic', 'generated_by', 'approved_by', 'approved_at', 'created_at', 'content_hash']);

        return response()->json(['reports' => $reports, 'cohorts' => $this->terms($request)->with(['program:id,code', 'academicTerm:id,name'])->get(), 'kinds' => ReportBuilder::KINDS], headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request, ReportBuilder $builder)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $data = $request->validate(['program_term_id' => ['required', 'integer'], 'kind' => ['required', Rule::in(ReportBuilder::KINDS)], 'include_synthetic' => ['sometimes', 'boolean']]);
        $report = ReportManagement::generate($request->user(), (int) $data['program_term_id'], $data['kind'], (bool) ($data['include_synthetic'] ?? false));

        return response()->json(['data' => $report], 201);
    }

    public function show(Request $request, int $report)
    {
        return response()->json(['data' => self::present($this->reports($request)->findOrFail($report))], headers: ['Cache-Control' => 'no-store']);
    }

    public function approve(Request $request, int $report)
    {
        $record = ReportManagement::approve($request->user(), $report);

        return response()->json(['data' => $record]);
    }

    public function download(Request $request, int $report)
    {
        $record = $this->reports($request)->findOrFail($report);
        $format = $request->validate(['format' => ['nullable', Rule::in(['csv', 'xlsx', 'pdf'])]])['format'] ?? 'csv';
        Audit::record($request->user(), 'report.exported', $record, ['format' => $format, 'content_hash' => $record->content_hash], $record->programTerm->program_id);
        if ($format === 'xlsx') {
            return $this->xlsx($request, $record);
        }
        if ($format === 'pdf') {
            return $this->pdf($request, $record);
        }

        return response()->streamDownload(function () use ($record) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::columns($record), escape: '');
            foreach (self::rows($record) as $row) {
                // Prevent spreadsheet formula interpretation of user-supplied names/text.
                $safe = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($stream, $safe, escape: '');
            }
            fclose($stream);
        }, $this->filename($record, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /**
     * Spec §17A-C (FR-22): XLSX export of the same frozen, scope-checked report snapshot.
     * Text is written as explicit string cells, so spreadsheet software never evaluates it as a formula.
     */
    private function xlsx(Request $request, GeneratedReport $record)
    {
        $record->loadMissing('programTerm.program', 'programTerm.academicTerm');
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Report');
        $put = function ($sheet, int $col, int $row, $value): void {
            $cell = Coordinate::stringFromColumnIndex($col).$row;
            if (is_int($value) || is_float($value)) {
                $sheet->setCellValue($cell, $value);
            } else {
                $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        };
        foreach (self::columns($record) as $c => $column) {
            $put($sheet, $c + 1, 1, $column);
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        foreach (self::rows($record) as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $put($sheet, $c + 1, $r + 2, $value);
            }
        }
        $info = $book->createSheet()->setTitle('Export details');
        foreach ($this->exportDetails($request, $record) as $i => [$label, $value]) {
            $put($info, 1, $i + 1, $label);
            $put($info, 2, $i + 1, $value);
        }
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $this->filename($record, 'xlsx'), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store']);
    }

    /** @return list<array{0: string, 1: string}> report context printed with every XLSX/PDF export (spec §17A-C) */
    private function exportDetails(Request $request, GeneratedReport $record): array
    {
        $record->loadMissing('programTerm.program', 'programTerm.academicTerm');

        return [
            ['Report', (string) $record->kind],
            ['Program', (string) ($record->programTerm->program->code ?? '')],
            ['Academic term', (string) ($record->programTerm->academicTerm->name ?? '')],
            ['Report generated', (string) $record->created_at],
            ['Approval', $record->approved_at ? 'Approved '.$record->approved_at : 'Not approved'],
            ['Test data', $record->contains_synthetic ? self::SYNTHETIC_LABEL.' (see the test_record column)' : 'None'],
            ['Content hash', (string) $record->content_hash],
            ['Exported at', now()->toDateTimeString()],
            ['Exported by', (string) $request->user()->name],
        ];
    }

    /**
     * FR-22: PDF export of the same frozen, scope-checked report snapshot. All values are HTML-escaped,
     * and dompdf runs with remote resources and PHP disabled, so report text cannot load or execute anything.
     */
    private function pdf(Request $request, GeneratedReport $record)
    {
        $html = view('reports.pdf', [
            'title' => 'InternMatch '.str_replace('_', ' ', (string) $record->kind).' report',
            'notice' => $record->contains_synthetic ? self::SYNTHETIC_LABEL.': this report includes test records.' : null,
            'details' => $this->exportDetails($request, $record),
            'columns' => self::columns($record),
            'rows' => self::rows($record),
        ])->render();
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setDefaultFont('DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', count($record->payload['columns']) > 6 ? 'landscape' : 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($record, 'pdf').'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
