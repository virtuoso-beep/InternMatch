<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\GeneratedReport;
use App\Models\ProgramTerm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportManagement
{
    public static function terms(User $actor): Builder
    {
        abort_unless($actor->hasPermission(Permission::ViewReports), 403);

        return ProgramTerm::whereIn('program_id', $actor->programs()->select('programs.id'));
    }

    public static function query(User $actor): Builder
    {
        return GeneratedReport::whereIn('program_term_id', self::terms($actor)->select('id'))
            ->when($actor->role === Role::Dean, fn ($q) => $q->whereNotNull('approved_at'));
    }

    public static function generate(User $actor, int $termId, string $kind): GeneratedReport
    {
        abort_unless($actor->role === Role::Coordinator, 403);
        abort_unless(in_array($kind, ReportBuilder::KINDS, true), 422);
        $term = self::terms($actor)->findOrFail($termId);

        return DB::transaction(function () use ($actor, $term, $kind) {
            $payload = app(ReportBuilder::class)->build($term, $kind);
            $report = GeneratedReport::create(['program_term_id' => $term->id, 'kind' => $kind, 'payload' => $payload, 'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), 'generated_by' => $actor->id]);
            Audit::record($actor, 'report.generated', $report, ['kind' => $kind, 'rows' => count($payload['rows']), 'content_hash' => $report->content_hash], $term->program_id);

            return $report;
        });
    }

    public static function approve(User $actor, int $id): GeneratedReport
    {
        abort_unless($actor->role === Role::Coordinator, 403);

        return DB::transaction(function () use ($actor, $id) {
            $report = self::query($actor)->lockForUpdate()->findOrFail($id);
            abort_if($report->approved_at, 409, 'Report has already been approved.');
            $report->update(['approved_by' => $actor->id, 'approved_at' => now()]);
            Audit::record($actor, 'report.approved', $report, ['content_hash' => $report->content_hash], $report->programTerm->program_id);

            return $report;
        });
    }
}
