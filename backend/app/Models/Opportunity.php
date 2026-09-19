<?php

namespace App\Models;

use App\Enums\OpportunityStatus;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['host_establishment_id', 'academic_term_id', 'title', 'description', 'tasks', 'capacity', 'status', 'starts_on', 'ends_on'])]
class Opportunity extends Model
{
    /** @return HasMany<OpportunityInterest, $this> */
    public function interests(): HasMany
    {
        return $this->hasMany(OpportunityInterest::class);
    }

    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => OpportunityStatus::class,
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<HostEstablishment, $this> */
    public function hostEstablishment(): BelongsTo
    {
        return $this->belongsTo(HostEstablishment::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** @return BelongsToMany<Program, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'opportunity_program')->withPivot('capacity')->withTimestamps();
    }

    /** @return BelongsToMany<Competency, $this> */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'opportunity_competency')->withPivot(['minimum_level', 'is_required'])->withTimestamps();
    }

    /** @return HasMany<Placement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
