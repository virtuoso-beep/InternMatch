<?php

namespace App\Models;

use Database\Factories\EvaluationRubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'version', 'name', 'is_active'])]
class EvaluationRubric extends Model
{
    /** @use HasFactory<EvaluationRubricFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<EvaluationCriterion, $this> */
    public function criteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class);
    }

    /** @return HasMany<Evaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
