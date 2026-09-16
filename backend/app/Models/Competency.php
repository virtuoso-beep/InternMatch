<?php

namespace App\Models;

use Database\Factories\CompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]
class Competency extends Model
{
    /** @use HasFactory<CompetencyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<ProgramTerm, $this> */
    public function programTerms(): BelongsToMany
    {
        return $this->belongsToMany(ProgramTerm::class, 'competency_program_term')->withPivot(['target_level'])->withTimestamps();
    }

    /** @return BelongsToMany<Opportunity, $this> */
    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'opportunity_competency')->withPivot(['minimum_level', 'is_required'])->withTimestamps();
    }

    /** @return HasMany<StudentCompetency, $this> */
    public function studentCompetencies(): HasMany
    {
        return $this->hasMany(StudentCompetency::class);
    }
}
