<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use App\Services\EvaluationRubricConfiguration;
use Illuminate\Console\Command;

/**
 * Applies the spec §13A supervisor evaluation rubric (17 Nov 2026) to BSIT, BSA and BSCRIM.
 * Three areas weighted 50/30/20, each area the average of its 1–5 indicator ratings. Expressed as
 * per-indicator weights (career 10 × 5, general 6 × 5, core values 5 × 4) so the existing weighted
 * scoring produces exactly the spec's weighted average. Idempotent: a program already on this rubric
 * name is skipped; a new version is created otherwise (earlier versions and evaluations are kept).
 */
class ConfigureSpecRubrics extends Command
{
    protected $signature = 'internmatch:configure-spec-rubrics {--dry-run : Show what would change without writing}';

    protected $description = 'Configure the spec §13A evaluation rubric for BSIT, BSA and BSCRIM';

    public const NAME = 'UM practicum evaluation (spec §13A)';

    public const REFERENCE = 'InternMatch spec §13A (17 Nov 2026): areas and weights from the Accountancy practicum evaluation form; core-values indicators are the interim list until UM\'s official form indicators are supplied.';

    private const CAREER = [
        'BSA' => ['Applies basic accounting principles to assigned tasks', 'Records transactions and prepares schedules accurately', 'Uses spreadsheets and accounting software effectively', 'Organizes and files financial documents properly', 'Keeps financial information confidential'],
        'BSIT' => ['Applies programming, networking, or IT-support knowledge to assigned tasks', 'Troubleshoots and resolves technical problems', 'Uses development, office, or IT tools effectively', 'Documents work, code, or procedures clearly', 'Follows IT security and data-handling policies'],
        'BSCRIM' => ['Applies knowledge of law-enforcement and public-safety procedures', 'Prepares clear and accurate reports and documentation', 'Follows the chain of command, protocols, and safety procedures', 'Shows physical readiness and discipline in assigned duties', 'Handles confidential and sensitive information properly'],
    ];

    private const GENERAL = ['Attendance and punctuality', 'Quality and completeness of work', 'Communication with supervisors and co-workers', 'Initiative and willingness to learn', 'Teamwork and cooperation'];

    private const CORE_VALUES = ['Honesty and integrity', 'Respect for others', 'Responsibility and accountability', 'Commitment to excellence'];

    public static function criteria(string $code): array
    {
        $rows = [];
        foreach (self::CAREER[$code] as $name) {
            $rows[] = ['name' => 'Career-specific: '.$name, 'max_score' => 5, 'weight' => 10];
        }
        foreach (self::GENERAL as $name) {
            $rows[] = ['name' => 'General: '.$name, 'max_score' => 5, 'weight' => 6];
        }
        foreach (self::CORE_VALUES as $name) {
            $rows[] = ['name' => 'Core values: '.$name, 'max_score' => 5, 'weight' => 5];
        }

        return $rows;
    }

    public function handle(): int
    {
        $actor = User::where('role', Role::Admin)->where('status', 'active')->orderBy('id')->first();
        if (! $actor) {
            $this->error('An active administrator account is required to attribute the change.');

            return self::FAILURE;
        }
        foreach (array_keys(self::CAREER) as $code) {
            $program = Program::where('code', $code)->first();
            if (! $program) {
                $this->warn("$code: program not found, skipped.");

                continue;
            }
            if ($program->evaluation_rubric_id && $program->evaluationRubric?->name === self::NAME) {
                $this->line("$code: already on the spec rubric, skipped.");

                continue;
            }
            if ($this->option('dry-run')) {
                $this->line("$code: would configure the spec rubric.");

                continue;
            }
            $rubric = EvaluationRubricConfiguration::configure($actor, $program, ['name' => self::NAME, 'approval_reference' => self::REFERENCE, 'criteria' => self::criteria($code)]);
            $this->info("$code: configured rubric #{$rubric->id} (version {$rubric->version}).");
        }

        return self::SUCCESS;
    }
}
