<?php

namespace App\Models;

use App\Enums\PlacementStatus;
use Database\Factories\PlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_enrollment_id', 'host_establishment_id', 'opportunity_id', 'moa_id', 'supervisor_id', 'status', 'starts_on', 'ends_on', 'completed_at'])]
class Placement extends Model
{
    /** @use HasFactory<PlacementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PlacementStatus::class,
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /** @return BelongsTo<HostEstablishment, $this> */
    public function hostEstablishment(): BelongsTo
    {
        return $this->belongsTo(HostEstablishment::class);
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** @return BelongsTo<Moa, $this> */
    public function moa(): BelongsTo
    {
        return $this->belongsTo(Moa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** @return HasMany<PlacementDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(PlacementDecision::class);
    }

    /** @return HasMany<TimeLog, $this> */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /** @return HasMany<JournalEntry, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /** @return HasMany<Evaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /** @return HasMany<MonitoringFlag, $this> */
    public function monitoringFlags(): HasMany
    {
        return $this->hasMany(MonitoringFlag::class);
    }
}
