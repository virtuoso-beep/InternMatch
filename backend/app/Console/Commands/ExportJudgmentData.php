<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportJudgmentData extends Command
{
    protected $signature = 'ml:export-judgments';
    protected $description = 'Export approved judgments for ML training';

    public function handle()
    {
        $judgments = DB::table('placement_judgments')
            ->join('recommendations', 'placement_judgments.recommendation_id', '=', 'recommendations.id')
            ->whereNotNull('placement_judgments.approved_by')
            ->whereIn('placement_judgments.judgment', ['suitable', 'unsuitable'])
            ->select(
                'recommendations.similarity_score',
                'recommendations.distance_km',
                'recommendations.capacity_at_time',
                'recommendations.moa_status_at_time',
                'placement_judgments.judgment'
            )
            ->get();

        if ($judgments->isEmpty()) {
            $this->info('No approved judgments found for export.');
            return 0;
        }

        $csvData = "similarity_score,distance_km,capacity_at_time,moa_active,label\n";

        foreach ($judgments as $j) {
            $similarity = $j->similarity_score;
            $distance = is_null($j->distance_km) ? '' : $j->distance_km;
            $capacity = $j->capacity_at_time;
            $moa_active = (strtolower($j->moa_status_at_time) === 'active') ? 1 : 0;
            $label = ($j->judgment === 'suitable') ? 1 : 0;

            $csvData .= "{$similarity},{$distance},{$capacity},{$moa_active},{$label}\n";
        }

        Storage::disk('local')->put('ml/judgment_export.csv', $csvData);

        $this->info('Successfully exported ' . $judgments->count() . ' judgments to storage/app/ml/judgment_export.csv');
        return 0;
    }
}
