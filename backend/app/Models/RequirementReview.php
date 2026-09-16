<?php

namespace App\Models;

use App\SubmissionStatus;
use Database\Factories\RequirementReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requirement_submission_id', 'reviewed_by', 'decision', 'comments', 'reviewed_at'])]
class RequirementReview extends Model
{
    /** @use HasFactory<RequirementReviewFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'decision' => SubmissionStatus::class,
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<RequirementSubmission, $this> */
    public function requirementSubmission(): BelongsTo
    {
        return $this->belongsTo(RequirementSubmission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
