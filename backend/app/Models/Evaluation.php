<?php

namespace App\Models;

use App\EvaluationStatus;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['placement_id', 'evaluation_rubric_id', 'evaluator_id', 'period', 'status', 'comments', 'submitted_at'])]
class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Placement, $this> */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /** @return BelongsTo<EvaluationRubric, $this> */
    public function evaluationRubric(): BelongsTo
    {
        return $this->belongsTo(EvaluationRubric::class);
    }

    /** @return BelongsTo<User, $this> */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /** @return HasMany<EvaluationScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
