<?php

namespace App\Models;

use Database\Factories\ProgramTermRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_term_id', 'requirement_type_id', 'is_required', 'required_before_deployment', 'due_at'])]
class ProgramTermRequirement extends Model
{
    /** @use HasFactory<ProgramTermRequirementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'required_before_deployment' => 'boolean',
            'due_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ProgramTerm, $this> */
    public function programTerm(): BelongsTo
    {
        return $this->belongsTo(ProgramTerm::class);
    }

    /** @return BelongsTo<RequirementType, $this> */
    public function requirementType(): BelongsTo
    {
        return $this->belongsTo(RequirementType::class);
    }

    /** @return HasMany<RequirementSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class);
    }
}
