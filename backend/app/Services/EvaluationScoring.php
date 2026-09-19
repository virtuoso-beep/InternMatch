<?php

namespace App\Services;

use App\Models\Evaluation;

class EvaluationScoring
{
    public function percentage(Evaluation $evaluation): ?float
    {
        $criteria = $evaluation->evaluationRubric->criteria;
        $scores = $evaluation->scores->keyBy('evaluation_criterion_id');
        if ($criteria->isEmpty() || $scores->count() !== $criteria->count()) {
            return null;
        }
        $weighted = 0;
        $weights = 0;
        foreach ($criteria as $criterion) {
            $score = $scores->get($criterion->id);
            if (! $score) {
                return null;
            }
            $weighted += (float) $score->score / (float) $criterion->max_score * (float) $criterion->weight;
            $weights += (float) $criterion->weight;
        }

        return round($weighted / $weights * 100, 4);
    }
}
