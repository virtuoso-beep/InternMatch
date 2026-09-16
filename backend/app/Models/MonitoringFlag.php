<?php

namespace App\Models;

use App\RiskSeverity;
use Database\Factories\MonitoringFlagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['placement_id', 'code', 'severity', 'description', 'raised_by', 'raised_at', 'resolved_by', 'resolved_at', 'resolution'])]
class MonitoringFlag extends Model
{
    /** @use HasFactory<MonitoringFlagFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'severity' => RiskSeverity::class,
            'raised_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Placement, $this> */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
