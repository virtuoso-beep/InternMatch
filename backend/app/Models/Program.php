<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'cluster', 'required_ojt_hours', 'internship_term', 'evaluation_rubric_id', 'is_active'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'required_ojt_hours' => 'integer',
        ];
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'program_competencies')->withTimestamps();
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coordinator_program')->withTimestamps();
    }

    /** @return HasMany<ProgramTerm, $this> */
    public function programTerms(): HasMany
    {
        return $this->hasMany(ProgramTerm::class);
    }

    /** @return BelongsToMany<Moa, $this> */
    public function moas(): BelongsToMany
    {
        return $this->belongsToMany(Moa::class, 'moa_program')->withTimestamps();
    }

    /** @return BelongsToMany<Opportunity, $this> */
    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'opportunity_program')->withTimestamps();
    }
}
