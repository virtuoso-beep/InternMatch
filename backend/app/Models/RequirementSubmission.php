<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\RequirementSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_enrollment_id', 'program_term_requirement_id', 'program_term_id', 'document_id', 'revision', 'status', 'submitted_at', 'notes'])]
class RequirementSubmission extends Model
{
    /** @use HasFactory<RequirementSubmissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /** @return BelongsTo<ProgramTermRequirement, $this> */
    public function programTermRequirement(): BelongsTo
    {
        return $this->belongsTo(ProgramTermRequirement::class);
    }

    /** @return BelongsTo<ProgramTerm, $this> */
    public function programTerm(): BelongsTo
    {
        return $this->belongsTo(ProgramTerm::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return HasMany<RequirementReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(RequirementReview::class);
    }
}
