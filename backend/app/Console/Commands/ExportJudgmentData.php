<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Logistic Regression training export.
 *  --source=real       (default) approved coordinator judgments on real (non-synthetic) students only.
 *  --source=synthetic  imported synthetic coordinator labels only; "For Review" rows (no label) are skipped.
 *  --source=all        both. Every row carries is_synthetic so training can always tell them apart.
 * For synthetic labels, similarity_score is the sheet's skill_match_score and capacity_at_time its available_slots.
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

        $rows = collect();
        if ($source !== 'synthetic') {
            $rows = $rows->concat($this->realJudgments());
        }
        if ($source !== 'real') {
            $rows = $rows->concat($this->syntheticLabels($perProgram === null ? null : (int) $perProgram));
        }

        if ($rows->isEmpty()) {
            $this->info("No labelled rows found for --source={$source}.");

            return self::SUCCESS;
        }

        $csv = "similarity_score,distance_km,capacity_at_time,moa_active,label,is_synthetic\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [$r['similarity_score'], $r['distance_km'] ?? '', $r['capacity_at_time'], $r['moa_active'], $r['label'], $r['is_synthetic']])."\n";
        }
        Storage::disk('local')->put(self::FILE, $csv);

        $synthetic = $rows->where('is_synthetic', 1)->count();
        $this->info("Exported {$rows->count()} rows (real: ".($rows->count() - $synthetic).", synthetic: {$synthetic}) to ".Storage::disk('local')->path(self::FILE));

        return self::SUCCESS;
    }

    private function realJudgments(): Collection
    {
        return DB::table('placement_judgments')
            ->join('recommendations', 'placement_judgments.recommendation_id', '=', 'recommendations.id')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'recommendations.student_enrollment_id')
            ->where('student_enrollments.is_synthetic', false)
            ->whereNotNull('placement_judgments.approved_by')
            ->whereIn('placement_judgments.judgment', ['suitable', 'unsuitable'])
            ->select('recommendations.similarity_score', 'recommendations.distance_km', 'recommendations.capacity_at_time', 'recommendations.moa_status_at_time', 'placement_judgments.judgment')
            ->get()
            ->map(fn ($j) => [
                'similarity_score' => $j->similarity_score, 'distance_km' => $j->distance_km, 'capacity_at_time' => $j->capacity_at_time,
                'moa_active' => strtolower((string) $j->moa_status_at_time) === 'active' ? 1 : 0,
                'label' => $j->judgment === 'suitable' ? 1 : 0, 'is_synthetic' => 0,
            ]);
    }

    /**
     * Synthetic coordinator labels. With $perProgram, each program contributes at most that many rows, split
     * evenly between suitable and unsuitable (lowest IDs first), topped up from the other class when one is short.
     */
    private function syntheticLabels(?int $perProgram = null): Collection
    {
        $labels = DB::table('coordinator_labels')->where('is_synthetic', true)->whereNotNull('suitable')->orderBy('id')->get();
        if ($perProgram !== null) {
            $labels = $labels->groupBy('program_id')->flatMap(function (Collection $group) use ($perProgram) {
                [$suitable, $unsuitable] = $group->partition(fn ($l) => (bool) $l->suitable);
                $half = intdiv($perProgram, 2);
                $takeSuitable = min($suitable->count(), max($half, $perProgram - $unsuitable->count()));
                $takeUnsuitable = min($unsuitable->count(), $perProgram - $takeSuitable);

                return $suitable->take($takeSuitable)->concat($unsuitable->take($takeUnsuitable));
            })->sortBy('id')->values();
        }

        return $labels
            ->map(fn ($l) => [
                'similarity_score' => $l->skill_match_score, 'distance_km' => $l->distance_km, 'capacity_at_time' => $l->available_slots,
                'moa_active' => $l->moa_valid ? 1 : 0, 'label' => $l->suitable ? 1 : 0, 'is_synthetic' => 1,
            ]);
    }
}
