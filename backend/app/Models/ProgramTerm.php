<?php

namespace App\Models;

use Database\Factories\ProgramTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'academic_term_id', 'required_minutes', 'monitoring_rules'])]
class ProgramTerm extends Model
{
    /** @use HasFactory<ProgramTermFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'required_minutes' => 'integer',
            'monitoring_rules' => 'array',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_term_user')->withTimestamps();
    }

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /** @return HasMany<ProgramTermRequirement, $this> */
    public function requirements(): HasMany
    {
        return $this->hasMany(ProgramTermRequirement::class);
    }

    /** @return BelongsToMany<Competency, $this> */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'competency_program_term')->withPivot(['target_level'])->withTimestamps();
    }
}
