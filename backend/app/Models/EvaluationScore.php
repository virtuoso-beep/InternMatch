<?php

namespace App\Models;

use Database\Factories\EvaluationScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'evaluation_criterion_id', 'evaluation_rubric_id', 'score', 'comments'])]
class EvaluationScore extends Model
{
    /** @use HasFactory<EvaluationScoreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Evaluation, $this> */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /** @return BelongsTo<EvaluationCriterion, $this> */
    public function evaluationCriterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class);
    }

    /** @return BelongsTo<EvaluationRubric, $this> */
    public function evaluationRubric(): BelongsTo
    {
        return $this->belongsTo(EvaluationRubric::class);
    }
}
