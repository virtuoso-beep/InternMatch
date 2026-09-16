<?php

namespace App\Models;

use App\Enums\TimeLogStatus;
use Database\Factories\TimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['placement_id', 'work_date', 'time_in', 'time_out', 'break_minutes', 'credited_minutes', 'status', 'verified_by', 'verified_at', 'notes'])]
class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'work_date' => 'immutable_date',
            'time_in' => 'immutable_datetime',
            'time_out' => 'immutable_datetime',
            'break_minutes' => 'integer',
            'credited_minutes' => 'integer',
            'status' => TimeLogStatus::class,
            'verified_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Placement, $this> */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
