<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Recommendation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'generated_at' => 'immutable_datetime', 'similarity_score' => 'float',
            'distance_km' => 'float', 'capacity_at_time' => 'integer', 'rank' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Recommendation snapshots are immutable; create a new generation.');
        });
        static::deleting(function (): void {
            throw new LogicException('Recommendation snapshots are retained for placement review and audit.');
        });
    }
}
