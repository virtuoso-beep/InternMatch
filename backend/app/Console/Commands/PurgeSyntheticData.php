<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes every synthetic record (is_synthetic = 1) and everything that hangs off it, in foreign-key order,
 * inside one transaction. Without --force it is a dry run: the same deletes run and are rolled back, so the
 * printed counts are exact. Accounts listed in config('internmatch.synthetic_keep_student_numbers') are kept
 * (and stop being flagged synthetic); their synthetic placements, logs, evaluations and enrollments are removed.
 * Real records are never selected: every delete is keyed to a synthetic row id.
 */
class PurgeSyntheticData extends Command
{
    protected $signature = 'internmatch:purge-synthetic {--force : Actually delete (default is a dry run)}';

    protected $description = 'Remove all synthetic test data (dry run unless --force)';

    /** @var array<string, int> */
    private array $counts = [];

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->counts = []; // command instances are reused between calls in one process
        DB::beginTransaction();
        try {
            $this->purge();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Nothing was deleted: '.$e->getMessage());

            return self::FAILURE;
        }
        $force ? DB::commit() : DB::rollBack();

        $this->table(['Table', $force ? 'Deleted' : 'Would delete'], collect($this->counts)->filter()->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->info($force ? 'Synthetic data removed.' : 'Dry run only; nothing was changed. Re-run with --force to delete.');

        return self::SUCCESS;
    }

    private function ids(string $table, string $column = 'id'): array
    {
        return DB::table($table)->where('is_synthetic', true)->pluck($column)->all();
    }

    /**
     * Recommendation batch reports store student rows as JSON. A batch made only of synthetic students is
     * deleted; a mixed batch keeps its real-student rows and loses only the synthetic ones.
     */
    private function purgeBatchReports(array $enrollments): void
    {
        $synthetic = array_flip($enrollments);
        foreach (DB::table('recommendation_batch_reports')->orderBy('id')->get(['id', 'results']) as $batch) {
            $rows = json_decode($batch->results, true) ?: [];
            $real = array_values(array_filter($rows, fn ($row) => ! isset($row['enrollment_id']) || ! isset($synthetic[$row['enrollment_id']])));
            if (count($real) === count($rows)) {
                continue;
            }
            if ($real === []) {
                $this->counts['recommendation_batch_reports (synthetic-only, deleted)'] = ($this->counts['recommendation_batch_reports (synthetic-only, deleted)'] ?? 0)
                    + DB::table('recommendation_batch_reports')->where('id', $batch->id)->delete();
            } else {
                DB::table('recommendation_batch_reports')->where('id', $batch->id)->update(['results' => json_encode($real), 'contains_synthetic' => false]);
                $this->counts['recommendation_batch_reports (mixed, synthetic rows removed)'] = ($this->counts['recommendation_batch_reports (mixed, synthetic rows removed)'] ?? 0) + 1;
                $this->counts['  synthetic rows removed from mixed batches'] = ($this->counts['  synthetic rows removed from mixed batches'] ?? 0) + count($rows) - count($real);
            }
        }
    }

    private function delete(string $table, callable $where): void
    {
        $this->counts[$table] = ($this->counts[$table] ?? 0) + $where(DB::table($table))->delete();
    }

    private function purge(): void
    {
        $keepStudents = DB::table('students')->whereIn('student_number', config('internmatch.synthetic_keep_student_numbers', []))->where('is_synthetic', true)->pluck('id')->all();
        $keepUsers = DB::table('students')->whereIn('id', $keepStudents)->pluck('user_id')->all();

        $enrollments = DB::table('student_enrollments')->where('is_synthetic', true)->pluck('id')->all();
        $placements = DB::table('placements')->where('is_synthetic', true)->orWhereIn('student_enrollment_id', $enrollments)->pluck('id')->all();
        $terms = $this->ids('program_terms');
        $academic = $this->ids('academic_terms');
        $opportunities = $this->ids('opportunities');
        $moas = $this->ids('moas');
        $hosts = $this->ids('host_establishments');
        $competencies = $this->ids('competencies');
        $rubrics = $this->ids('evaluation_rubrics');
        $students = array_values(array_diff($this->ids('students'), $keepStudents));
        $users = array_values(array_diff($this->ids('users'), $keepUsers));
        $conversations = DB::table('conversations')->where('is_synthetic', true)->orWhereIn('student_enrollment_id', $enrollments)->orWhereIn('placement_id', $placements)->pluck('id')->all();
        $recommendations = DB::table('recommendations')->whereIn('student_enrollment_id', $enrollments)->orWhereIn('opportunity_id', $opportunities)->pluck('id')->all();
        $submissions = DB::table('requirement_submissions')->whereIn('student_enrollment_id', $enrollments)->pluck('id')->all();

        $this->purgeBatchReports($enrollments);
        $this->delete('audit_logs', fn ($q) => $q->where('is_synthetic', true));
        $this->delete('chat_messages', fn ($q) => $q->where('is_synthetic', true)->orWhereIn('conversation_id', $conversations));
        $this->delete('conversations', fn ($q) => $q->whereIn('id', $conversations));
        $this->delete('notifications', fn ($q) => $q->where('is_synthetic', true)->orWhere(fn ($n) => $n->where('notifiable_type', (new User)->getMorphClass())->whereIn('notifiable_id', $users)));
        $this->delete('evaluation_scores', fn ($q) => $q->whereIn('evaluation_id', DB::table('evaluations')->whereIn('placement_id', $placements)->select('id')));
        $this->delete('evaluations', fn ($q) => $q->whereIn('placement_id', $placements));
        foreach (['journal_entries', 'time_logs', 'monitoring_flags', 'monitoring_records', 'attendance_absences', 'placement_decisions'] as $table) {
            $this->delete($table, fn ($q) => $q->whereIn('placement_id', $placements));
        }
        $this->delete('placement_proposal_items', fn ($q) => $q->whereIn('student_enrollment_id', $enrollments)->orWhereIn('opportunity_id', $opportunities)->orWhereIn('recommendation_id', $recommendations));
        $this->delete('placement_judgments', fn ($q) => $q->whereIn('recommendation_id', $recommendations));
        $this->delete('recommendations', fn ($q) => $q->whereIn('id', $recommendations));
        $this->delete('recommendation_generations', fn ($q) => $q->whereIn('student_enrollment_id', $enrollments));
        $this->delete('placements', fn ($q) => $q->whereIn('id', $placements));
        $this->delete('coordinator_labels', fn ($q) => $q->where('is_synthetic', true));
        $this->delete('opportunity_interests', fn ($q) => $q->whereIn('student_enrollment_id', $enrollments)->orWhereIn('opportunity_id', $opportunities));
        $this->delete('requirement_reviews', fn ($q) => $q->whereIn('requirement_submission_id', $submissions));
        $this->delete('requirement_submissions', fn ($q) => $q->whereIn('id', $submissions));
        $this->delete('event_attendances', fn ($q) => $q->whereIn('student_enrollment_id', $enrollments));
        $this->delete('student_enrollments', fn ($q) => $q->whereIn('id', $enrollments));
        $this->delete('generated_reports', fn ($q) => $q->whereIn('program_term_id', $terms));
        $this->delete('competency_program_term', fn ($q) => $q->whereIn('program_term_id', $terms)->orWhereIn('competency_id', $competencies));
        $this->delete('program_term_requirements', fn ($q) => $q->whereIn('program_term_id', $terms));
        $this->delete('placement_proposals', fn ($q) => $q->whereIn('program_term_id', $terms));
        $this->delete('program_terms', fn ($q) => $q->whereIn('id', $terms));
        $this->delete('opportunity_competency', fn ($q) => $q->whereIn('opportunity_id', $opportunities)->orWhereIn('competency_id', $competencies));
        $this->delete('opportunity_program', fn ($q) => $q->whereIn('opportunity_id', $opportunities));
        $this->delete('embeddings', fn ($q) => $q->where(fn ($e) => $e->where('owner_type', (new Opportunity)->getMorphClass())->whereIn('owner_id', $opportunities))
            ->orWhere(fn ($e) => $e->where('owner_type', (new Student)->getMorphClass())->whereIn('owner_id', $students)));
        $this->delete('opportunities', fn ($q) => $q->whereIn('id', $opportunities));
        $this->delete('moa_program', fn ($q) => $q->whereIn('moa_id', $moas));
        $this->delete('moas', fn ($q) => $q->whereIn('id', $moas));
        $this->delete('host_program_capacity', fn ($q) => $q->whereIn('host_establishment_id', $hosts)->orWhereIn('academic_term_id', $academic));
        $this->delete('import_host_program', fn ($q) => $q->whereIn('host_establishment_id', $hosts));
        $this->delete('host_establishments', fn ($q) => $q->whereIn('id', $hosts));
        $this->delete('academic_terms', fn ($q) => $q->whereIn('id', $academic));
        $this->delete('student_competencies', fn ($q) => $q->whereIn('student_id', $students)->orWhereIn('competency_id', $competencies));
        $this->delete('program_competencies', fn ($q) => $q->whereIn('competency_id', $competencies));
        $this->delete('competencies', fn ($q) => $q->whereIn('id', $competencies));
        $this->delete('students', fn ($q) => $q->whereIn('id', $students));
        $this->delete('users', fn ($q) => $q->whereIn('id', $users));
        $this->delete('evaluation_criteria', fn ($q) => $q->whereIn('evaluation_rubric_id', $rubrics));
        $this->delete('evaluation_rubrics', fn ($q) => $q->whereIn('id', $rubrics));
        $this->delete('role_permission_references', fn ($q) => $q->where('is_synthetic', true));
        $this->delete('synthetic_source_rows', fn ($q) => $q);

        // Kept accounts become ordinary accounts.
        DB::table('students')->whereIn('id', $keepStudents)->update(['is_synthetic' => false]);
        DB::table('users')->whereIn('id', $keepUsers)->update(['is_synthetic' => false]);
        $this->counts['kept accounts'] = 0;
        $this->line('Kept accounts: '.count($keepUsers));
    }
}
