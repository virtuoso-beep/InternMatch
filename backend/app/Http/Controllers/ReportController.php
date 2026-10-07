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
        Audit::record($request->user(), 'report.exported', $record, ['format' => 'csv', 'content_hash' => $record->content_hash], $record->programTerm->program_id);

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
}
