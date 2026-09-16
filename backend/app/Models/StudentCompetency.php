<?php

namespace App\Models;

use Database\Factories\StudentCompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'competency_id', 'level', 'assessed_at'])]
class StudentCompetency extends Model
{
    /** @use HasFactory<StudentCompetencyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'assessed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Competency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /** @return HasMany<CompetencyEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(CompetencyEvidence::class);
    }
}
