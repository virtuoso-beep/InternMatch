<?php

namespace App\Models;

use Database\Factories\OpportunityInterestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_enrollment_id', 'opportunity_id', 'expressed_at', 'withdrawn_at'])]
class OpportunityInterest extends Model
{
    /** @use HasFactory<OpportunityInterestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['expressed_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
