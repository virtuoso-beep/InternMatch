<?php

namespace App\Models;

use Database\Factories\EvaluationCriterionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['evaluation_rubric_id', 'name', 'max_score', 'weight', 'sort_order'])]
class EvaluationCriterion extends Model
{
    /** @use HasFactory<EvaluationCriterionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'weight' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<EvaluationRubric, $this> */
    public function evaluationRubric(): BelongsTo
    {
        return $this->belongsTo(EvaluationRubric::class);
    }

    /** @return HasMany<EvaluationScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
