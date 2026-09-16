<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\PlacementStatus;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_id', 'program_term_id', 'year_level', 'required_minutes', 'status', 'enrolled_on', 'target_completion_on'])]
class StudentEnrollment extends Model
{
    /** @return HasMany<OpportunityInterest, $this> */
    public function interests(): HasMany
    {
        return $this->hasMany(OpportunityInterest::class);
    }

    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'required_minutes' => 'integer',
            'status' => EnrollmentStatus::class,
            'enrolled_on' => 'immutable_date',
            'target_completion_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<ProgramTerm, $this> */
    public function programTerm(): BelongsTo
    {
        return $this->belongsTo(ProgramTerm::class);
    }

    /** @return HasOne<Placement, $this> */
    public function currentPlacement(): HasOne
    {
        return $this->hasOne(Placement::class)->whereIn('status', [
            PlacementStatus::Pending,
            PlacementStatus::Approved,
            PlacementStatus::Active,
        ]);
    }

    /** @return HasMany<Placement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    /** @return HasMany<RequirementSubmission, $this> */
    public function requirementSubmissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class);
    }
}
