<?php

namespace App\Models;

use Database\Factories\HostEstablishmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'industry', 'address', 'city', 'latitude', 'longitude', 'contact_name', 'contact_email', 'contact_number', 'description', 'is_active'])]
class HostEstablishment extends Model
{
    /** @use HasFactory<HostEstablishmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'host_establishment_user')->withTimestamps();
    }

    /** @return HasMany<Moa, $this> */
    public function moas(): HasMany
    {
        return $this->hasMany(Moa::class);
    }

    /** @return HasMany<Opportunity, $this> */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /** @return HasMany<Placement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
