<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\RequirementType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileBsitCatalog extends Command
{
    protected $signature = 'internmatch:reconcile-bsit {--apply : Apply the reviewed changes after taking a database backup}';
    protected $description = 'Reconcile the confirmed BSIT catalog; preserve accounts, operational records, and other programs';

    public function handle(): int
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();
        $obsolete = ['medical-clearance', 'parental-consent', 'moa-copy', 'insurance-certificate'];
        $confirmed = ['web-development', 'programming', 'database-management', 'networking'];
        $types = RequirementType::whereIn('code', $obsolete)->whereDoesntHave('programTermRequirements')
            ->whereNotIn('id', DB::table('program_requirement_templates')->select('requirement_type_id'))->get();
        $links = $program->competencies()->whereNotIn('code', $confirmed)->get();
        $this->line(json_encode(['unused_placeholder_requirements' => $types->pluck('code'), 'unconfirmed_bsit_competency_links' => $links->pluck('code')], JSON_PRETTY_PRINT));
        if (! $this->option('apply')) {
            $this->info('Preview only. Take a database backup before using --apply.');
            return self::SUCCESS;
        }
        DB::transaction(function () use ($program, $types, $links) {
            (new DatabaseSeeder)->run();
            foreach ($types as $type) {
                // Recheck usage inside the transaction; FK constraints also prevent unsafe deletion.
                if (! $type->programTermRequirements()->exists() && ! DB::table('program_requirement_templates')->where('requirement_type_id', $type->id)->exists()) {
                    $type->delete();
                }
            }
            $program->competencies()->detach($links->modelKeys());
            foreach ($links as $competency) {
                if (! DB::table('program_competencies')->where('competency_id', $competency->id)->exists()
                    && ! $competency->programTerms()->exists() && ! $competency->opportunities()->exists()
                    && ! $competency->studentCompetencies()->exists()) {
                    $competency->delete();
                }
            }
        });
        $this->info('Confirmed BSIT catalog reconciled. Accounts and operational records preserved.');
        return self::SUCCESS;
    }
}
