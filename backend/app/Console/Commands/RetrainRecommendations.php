<?php

namespace App\Console\Commands;

use App\Services\RecommendationLearning;
use Illuminate\Console\Command;

/**
 * Same action as the administrator's "Update recommendations" button (spec §9): check the 30/30 rule, train
 * and evaluate, and install the model only if it passes. The run is recorded either way.
 */
class RetrainRecommendations extends Command
{
    protected $signature = 'ml:retrain {--test-data : Train on the flagged synthetic labels instead of real coordinator judgments (needs ML_ALLOW_SYNTHETIC_MODEL=1)}';

    protected $description = 'Update recommendations from coordinator judgments (installs the model only if it passes)';

    public function handle(RecommendationLearning $learning): int
    {
        $testData = (bool) $this->option('test-data');
        if ($testData && ! RecommendationLearning::testModeAllowed()) {
            $this->error('Test data can be used only when learning from test data is switched on (ML_ALLOW_SYNTHETIC_MODEL=1).');

            return self::INVALID;
        }
        if ($testData) {
            $coverage = $learning->testDataCoverage();
            $this->line("Test data: {$coverage['used']} of {$coverage['labelled']} labelled records can be scored with the system's own similarity and distance; {$coverage['skipped']} skipped (opportunity not imported or no embedding).");
        }
        $run = $learning->run(null, 'command', $testData);
        $this->line(($testData ? 'Test data' : 'Coordinator judgments').": suitable {$run->suitable}, not suitable {$run->not_suitable}, uncertain {$run->uncertain}.");
        $this->line(match ($run->result) {
            'updated' => 'Recommendations updated.',
            'not_enough_data' => 'Recommendations were not updated: there are not enough judgments yet.',
            'not_reliable' => 'Recommendations were not updated: the test showed they would not be reliable enough yet.',
            default => 'Recommendations were not updated: the update could not run.',
        });
        if ($run->reason) {
            $this->line('Reason: '.$run->reason);
        }

        return $run->result === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
