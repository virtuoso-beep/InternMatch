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
    protected $signature = 'ml:retrain {--test-data : Local test mode only: train on test data instead of real coordinator judgments}';

    protected $description = 'Update recommendations from coordinator judgments (installs the model only if it passes)';

    public function handle(RecommendationLearning $learning): int
    {
        $testData = (bool) $this->option('test-data');
        if ($testData && ! RecommendationLearning::testModeAllowed()) {
            $this->error('Test data can be used only in local test mode (ML_ALLOW_SYNTHETIC_MODEL=1).');

            return self::INVALID;
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
