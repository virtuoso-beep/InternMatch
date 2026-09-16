<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return array<string, array<string, string>> */
    private function checks(): array
    {
        return [
            'academic_terms' => [
                'academic_term_dates' => 'ends_on >= starts_on',
            ],
            'program_terms' => [
                'program_term_minutes' => 'required_minutes > 0',
            ],
            'student_enrollments' => [
                'enrollment_minutes' => 'required_minutes > 0',
                'enrollment_target_date' => 'target_completion_on IS NULL OR target_completion_on >= enrolled_on',
            ],
            'user_profiles' => [
                'profile_latitude' => 'latitude IS NULL OR latitude BETWEEN -90 AND 90',
                'profile_longitude' => 'longitude IS NULL OR longitude BETWEEN -180 AND 180',
            ],
            'host_establishments' => [
                'host_latitude' => 'latitude IS NULL OR latitude BETWEEN -90 AND 90',
                'host_longitude' => 'longitude IS NULL OR longitude BETWEEN -180 AND 180',
            ],
            'moas' => [
                'moa_dates' => 'effective_on IS NULL OR expires_on IS NULL OR expires_on >= effective_on',
                'active_moa_dates' => 'status <> \'active\' OR (effective_on IS NOT NULL AND expires_on IS NOT NULL)',
            ],
            'opportunities' => [
                'opportunity_capacity' => 'capacity >= 0',
                'opportunity_dates' => 'starts_on IS NULL OR ends_on IS NULL OR ends_on >= starts_on',
            ],
            'student_competencies' => [
                'competency_level' => 'level BETWEEN 0 AND 100',
            ],
            'competency_program_term' => [
                'curriculum_level' => 'target_level IS NULL OR target_level BETWEEN 0 AND 100',
            ],
            'opportunity_competency' => [
                'opportunity_level' => 'minimum_level IS NULL OR minimum_level BETWEEN 0 AND 100',
            ],
            'requirement_submissions' => [
                'submission_revision' => 'revision > 0',
                'submission_timestamp' => 'status = \'draft\' OR submitted_at IS NOT NULL',
            ],
            'placements' => [
                'placement_dates' => 'starts_on IS NULL OR ends_on IS NULL OR ends_on >= starts_on',
                'placement_moa' => 'status NOT IN (\'approved\', \'active\', \'completed\') OR moa_id IS NOT NULL',
                'placement_active_supervisor' => 'status NOT IN (\'active\', \'completed\') OR (supervisor_id IS NOT NULL AND starts_on IS NOT NULL)',
                'placement_completion' => 'status <> \'completed\' OR completed_at IS NOT NULL',
            ],
            'placement_decisions' => [
                'decision_reason' => 'LENGTH(TRIM(reason)) > 0',
            ],
            'time_logs' => [
                'time_log_order' => 'time_out IS NULL OR time_out > time_in',
                'time_log_minutes' => 'break_minutes >= 0 AND (credited_minutes IS NULL OR credited_minutes >= 0)',
                'verified_time_log' => 'status <> \'verified\' OR (verified_by IS NOT NULL AND verified_at IS NOT NULL AND credited_minutes IS NOT NULL AND time_out IS NOT NULL)',
            ],
            'evaluation_rubrics' => [
                'rubric_version' => 'version > 0',
            ],
            'evaluation_criteria' => [
                'criterion_scale' => 'max_score > 0 AND weight > 0',
            ],
            'evaluations' => [
                'evaluation_submission' => 'status <> \'submitted\' OR submitted_at IS NOT NULL',
            ],
            'evaluation_scores' => [
                'evaluation_score_nonnegative' => 'score >= 0',
            ],
            'monitoring_flags' => [
                'flag_resolution' => '(resolved_at IS NULL AND resolved_by IS NULL) OR (resolved_at IS NOT NULL AND resolved_by IS NOT NULL)',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->checks() as $table => $checks) {
            foreach ($checks as $name => $expression) {
                if (DB::getDriverName() === 'sqlite') {
                    $columns = array_column(DB::select("PRAGMA table_info(`{$table}`)"), 'name');
                    $rowExpression = preg_replace_callback('/\\b[a-z_]+\\b/', fn (array $match): string => in_array($match[0], $columns, true) ? 'NEW.'.$match[0] : $match[0], $expression);

                    foreach (['INSERT', 'UPDATE'] as $event) {
                        $trigger = $name.'_'.strtolower($event);
                        DB::unprepared("CREATE TRIGGER `{$trigger}` BEFORE {$event} ON `{$table}` FOR EACH ROW WHEN NOT ({$rowExpression}) BEGIN SELECT RAISE(ABORT, '{$name}'); END");
                    }
                } else {
                    DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->checks() as $table => $checks) {
            foreach ($checks as $name => $expression) {
                if (DB::getDriverName() === 'sqlite') {
                    DB::unprepared("DROP TRIGGER IF EXISTS `{$name}_insert`");
                    DB::unprepared("DROP TRIGGER IF EXISTS `{$name}_update`");
                } else {
                    DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$name}`");
                }
            }
        }
    }
};
