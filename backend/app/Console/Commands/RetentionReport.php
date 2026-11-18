<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Spec §17B retention, REPORT-ONLY. The periods are the team's proposals and need UMTC approval
 * (spec §22, still open), so this command never deletes anything: it only counts what WOULD be due.
 */
class RetentionReport extends Command
{
    protected $signature = 'internmatch:retention-report';

    protected $description = 'Report records past the proposed retention periods (report-only; deletes nothing)';

    public function handle(): int
    {
        $rows = [
            ['Chat messages', '1 year after deployment ends', DB::table('chat_messages')
                ->join('conversations', 'conversations.id', '=', 'chat_messages.conversation_id')
                ->join('placements', 'placements.id', '=', 'conversations.placement_id')
                ->whereNotNull('placements.ends_on')->where('placements.ends_on', '<', now()->subYear()->toDateString())->count()],
            ['Requirement documents', '1 year after the internship term ends', DB::table('requirement_submissions')
                ->join('program_terms', 'program_terms.id', '=', 'requirement_submissions.program_term_id')
                ->join('academic_terms', 'academic_terms.id', '=', 'program_terms.academic_term_id')
                ->where('academic_terms.ends_on', '<', now()->subYear()->toDateString())->count()],
            ['Placement, hours and evaluation records', '5 years after completion', DB::table('placements')
                ->whereNotNull('completed_at')->where('completed_at', '<', now()->subYears(5))->count()],
            ['Audit logs', '3 years', DB::table('audit_logs')->where('created_at', '<', now()->subYears(3))->count()],
        ];
        $this->warn('REPORT-ONLY: retention periods are proposals pending UMTC approval. Nothing is deleted.');
        $this->table(['Data', 'Proposed retention', 'Records past the period'], $rows);

        return self::SUCCESS;
    }
}
