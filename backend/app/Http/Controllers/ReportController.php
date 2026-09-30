<?php

namespace App\Http\Controllers;

use App\Enums\{Permission, Role};
use App\Models\{GeneratedReport, ProgramTerm};
use App\Services\{Audit, ReportBuilder};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private function terms(Request $request)
    {
        abort_unless($request->user()->hasPermission(Permission::ViewReports), 403);

        return ProgramTerm::whereIn('program_id', $request->user()->programs()->select('programs.id'));
    }

    private function reports(Request $request)
    {
        return GeneratedReport::whereIn('program_term_id', $this->terms($request)->select('id'))
            ->when($request->user()->role === Role::Dean, fn ($q) => $q->whereNotNull('approved_at'));
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
        $term = $this->terms($request)->findOrFail($data['program_term_id']);
        $report = DB::transaction(function () use ($request, $builder, $term, $data) {
            $payload = $builder->build($term, $data['kind']);
            $report = GeneratedReport::create(['program_term_id' => $term->id, 'kind' => $data['kind'], 'payload' => $payload, 'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), 'generated_by' => $request->user()->id]);
            Audit::record($request->user(), 'report.generated', $report, ['kind' => $report->kind, 'rows' => count($payload['rows']), 'content_hash' => $report->content_hash], $term->program_id);

            return $report;
        });

        return response()->json(['data' => $report], 201);
    }

    public function show(Request $request, int $report)
    {
        return response()->json(['data' => $this->reports($request)->findOrFail($report)], headers: ['Cache-Control' => 'no-store']);
    }

    public function approve(Request $request, int $report)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $record = DB::transaction(function () use ($request, $report) {
            $record = $this->reports($request)->lockForUpdate()->findOrFail($report);
            abort_if($record->approved_at, 409, 'Report has already been approved.');
            $record->update(['approved_by' => $request->user()->id, 'approved_at' => now()]);
            Audit::record($request->user(), 'report.approved', $record, ['content_hash' => $record->content_hash], $record->programTerm->program_id);

            return $record;
        });

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
