<?php

namespace App\Console\Commands;

use App\Models\HostEstablishment;
use App\Models\Program;
use App\Models\RequirementType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportAccountancySource extends Command
{
    protected $signature = 'internmatch:import-accountancy {json : Output of deploy/extract-accountancy.py} {--apply}';

    protected $description = 'Import BSA source rows, confirmed hours, hosts and requirement templates without fabricating historical evidence';

    public function handle(): int
    {
        $source = json_decode(file_get_contents($this->argument('json')), true, flags: JSON_THROW_ON_ERROR);
        $sheets = $source['sheets'];
        $hours = array_unique(array_column(array_column($sheets['Students Records'], 'values'), 7));
        if (count($hours) !== 1 || ! is_numeric($hours[0]) || $hours[0] <= 0 || $hours[0] > 10000) {
            throw new RuntimeException('Student rows do not supply one confirmed positive BSA hours requirement.');
        }
        foreach ($sheets['Students Records'] as $row) {
            if ($row['values'][1] !== 'BS Accountancy') {
                throw new RuntimeException('Unexpected program in BSA import.');
            }
        }
        $this->line(json_encode(['rows' => array_map('count', $sheets), 'bsa_hours' => $hours[0]], JSON_PRETTY_PRINT));
        if (! $this->option('apply')) {
            return self::SUCCESS;
        }
        DB::transaction(function () use ($source, $sheets, $hours) {
            $program = Program::where('code', 'BSA')->lockForUpdate()->sole();
            $beforeHours = $program->required_ojt_hours;
            $program->update(['required_ojt_hours' => (int) $hours[0]]);
            foreach ($sheets as $sheet => $rows) {
                foreach ($rows as $row) {
                    $note = match ($sheet) {
                        'Students Records' => 'Historical source: names and login emails not supplied; not provisioned as user accounts.',
                        'HTE Opportunities' => 'Review missing opportunity identifiers and academic term before publication.',
                        'Placement Records' => 'Review opportunity-to-host links and student account mapping before operational import.',
                        'Monitoring Records' => 'Aggregate historical hours retained; daily time logs and evidence files not supplied.',
                        'Evaluation Records' => 'Qualitative results retained; numeric rating scale and evaluator identity not supplied.',
                        default => null,
                    };
                    DB::table('coordinator_source_rows')->updateOrInsert([
                        'source_sha256' => $source['sha256'], 'sheet' => $sheet, 'source_row' => $row['row'],
                    ], ['program_id' => $program->id, 'source_name' => $source['source'], 'values' => json_encode($row['values'], JSON_THROW_ON_ERROR),
                        'review_note' => $note, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            foreach ($sheets['HTE Records'] as $row) {
                $v = $row['values'];
                // Prefix source-local identifiers to avoid collisions with existing HTE codes.
                $host = HostEstablishment::firstOrCreate(['code' => 'DAE-'.$v[0]], [
                    'name' => $v[1], 'industry' => $v[2], 'address' => $v[3], 'city' => $v[3],
                    'is_active' => $v[5] === 'Active',
                    'description' => 'Coordinator workbook: '.$v[0].'. MOA reported '.$v[8].', valid until '.substr($v[9], 0, 10).'. Agreement document/reference and effective date await verification.',
                ]);
            }
            foreach ($sheets['Internship Requirements'] as $row) {
                $v = $row['values'];
                $type = RequirementType::firstOrCreate(['code' => 'dae-'.strtolower($v[0])], [
                    'name' => $v[1], 'description' => $v[2].' Submission: '.$v[5].'.', 'kind' => 'document', 'is_active' => true,
                ]);
                DB::table('program_requirement_templates')->updateOrInsert(['program_id' => $program->id, 'requirement_type_id' => $type->id], [
                    'is_required' => $v[6] === 'Required', 'required_before_deployment' => $v[4] === 'Before Placement',
                ]);
            }
            DB::table('audit_logs')->insert(['actor_id' => null, 'program_id' => $program->id, 'action' => 'program.coordinator_data_imported',
                'subject_type' => $program->getMorphClass(), 'subject_id' => $program->id,
                'changes' => json_encode(['source_sha256' => $source['sha256'], 'hours_before' => $beforeHours, 'hours_after' => $program->required_ojt_hours, 'rows' => array_map('count', $sheets)]), 'created_at' => now()]);
        });
        $this->info('BSA settings and hosts imported; historical source rows retained for review. Other programs hours unchanged.');

        return self::SUCCESS;
    }
}
