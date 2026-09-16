<?php

namespace App\Models;

use App\MoaStatus;
use Database\Factories\MoaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['host_establishment_id', 'reference_number', 'status', 'effective_on', 'expires_on', 'max_interns_per_term', 'signatories', 'notes', 'document_id'])]
class Moa extends Model
{
    /** @use HasFactory<MoaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MoaStatus::class,
            'effective_on' => 'immutable_date',
            'expires_on' => 'immutable_date',
            'max_interns_per_term' => 'integer',
            'signatories' => 'array',
        ];
    }

    /** @return BelongsTo<HostEstablishment, $this> */
    public function hostEstablishment(): BelongsTo
    {
        return $this->belongsTo(HostEstablishment::class);
    }

    /** @return BelongsToMany<Program, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'moa_program')->withTimestamps();
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return HasMany<Placement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
