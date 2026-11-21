<?php

namespace App\Models;

use App\Enums\MoaStatus;
use Carbon\CarbonImmutable;
use Database\Factories\MoaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['host_establishment_id', 'reference_number', 'status', 'effective_on', 'expires_on', 'max_interns_per_term', 'signatories', 'notes', 'document_id', 'is_institution_wide'])]
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
            'is_institution_wide' => 'boolean',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /** Days of remaining validity at or below which an active agreement counts as expiring (spec §5). */
    public const EXPIRING_DAYS = 90;

    protected $appends = ['lifecycle_status'];

    /**
     * Spec §5 status computed from the dates: pending, active, expiring, expired.
     * A terminated agreement stays terminated; drafts and unsigned agreements are pending.
     */
    public function statusOn(?\DateTimeInterface $on = null): string
    {
        $today = CarbonImmutable::instance($on ?? now())->startOfDay();
        if ($this->status === MoaStatus::Terminated) {
            return 'terminated';
        }
        if ($this->expires_on && $this->expires_on->lt($today)) {
            return 'expired';
        }
        if ($this->status !== MoaStatus::Active || ! $this->effective_on || $this->effective_on->gt($today) || ! $this->expires_on) {
            return 'pending';
        }

        return $today->diffInDays($this->expires_on) <= self::EXPIRING_DAYS ? 'expiring' : 'active';
    }

    protected function lifecycleStatus(): Attribute
    {
        return Attribute::get(fn () => $this->statusOn());
    }

    public function coversProgram(int $programId): bool
    {
        return $this->is_institution_wide || $this->programs()->whereKey($programId)->exists();
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
