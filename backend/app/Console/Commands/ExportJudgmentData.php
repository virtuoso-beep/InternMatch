<?php

namespace App\Console\Commands;

use App\Services\RecommendationLearning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Research export of labelled judgments: the same rows the "Update recommendations" action uses
 * (App\Services\RecommendationLearning).
 *  --source=real       (default) latest approved coordinator judgment per pair on real students; uncertain excluded.
 *  --source=synthetic  test data only (synthetic coordinator labels); --per-program caps them per program.
 *  --source=all        both. Every row carries is_synthetic so analysis can always tell them apart.
 * Columns: student, program, similarity_score, distance_km, keyword_overlap, label, is_synthetic.
 */
class ExportJudgmentData extends Command
{
    protected $signature = 'ml:export-judgments {--source=real : real, synthetic or all} {--per-program= : synthetic labels: at most this many per program, half suitable and half not (lowest IDs first)}';

    protected $description = 'Export labelled placement judgments for Logistic Regression training';

    /** On the local disk (storage/app/private), i.e. backend/storage/app/private/ml/judgment_export.csv. */
    public const FILE = 'ml/judgment_export.csv';

    public function handle(): int
    {
        $source = (string) $this->option('source');
        if (! in_array($source, ['real', 'synthetic', 'all'], true)) {
            $this->error('--source must be real, synthetic or all.');

            return self::INVALID;
        }

        $perProgram = $this->option('per-program');
        if ($perProgram !== null && (! ctype_digit((string) $perProgram) || (int) $perProgram < 2)) {
            $this->error('--per-program must be a whole number of at least 2.');

            return self::INVALID;
        }

        $learning = app(RecommendationLearning::class);
        $rows = collect();
        if ($source !== 'synthetic') {
            $rows = $rows->concat($learning->realRows());
        }
        if ($source !== 'real') {
            $rows = $rows->concat($learning->testRows($perProgram === null ? null : (int) $perProgram));
        }

        if ($rows->isEmpty()) {
            $this->info("No labelled rows found for --source={$source}.");

            return self::SUCCESS;
        }

        // Same rows the "Update recommendations" action uses (latest approved judgment per pair; uncertain excluded).
        $csv = 'student,program,similarity_score,distance_km,keyword_overlap,label,is_synthetic
';
        foreach ($rows as $r) {
            $csv .= implode(',', [$r['student'], $r['program'], $r['similarity'], $r['distance_km'] ?? '', $r['keyword_overlap'] ?? '', $r['label'], $r['synthetic'] ? 1 : 0]).'
';
        }
        Storage::disk('local')->put(self::FILE, $csv);

        $test = $rows->where('synthetic', true)->count();
        $this->info("Exported {$rows->count()} rows (real: ".($rows->count() - $test).", test data: {$test}) to ".Storage::disk('local')->path(self::FILE));

        return self::SUCCESS;
    }
}
