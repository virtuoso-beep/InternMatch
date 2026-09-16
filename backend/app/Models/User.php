<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\Role;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => AccountStatus::class,
        ];
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->status === AccountStatus::Active && ($this->role?->allows($permission) ?? false);
    }

    public function isAssignedToProgramTerm(int $programTermId): bool
    {
        return $this->programTerms()->whereKey($programTermId)->exists();
    }

    public function isAssignedToHost(int $hostEstablishmentId): bool
    {
        return $this->hostEstablishments()->whereKey($hostEstablishmentId)->exists();
    }

    /** @return HasOne<UserProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /** @return HasOne<Student, $this> */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** @return BelongsToMany<ProgramTerm, $this> */
    public function programTerms(): BelongsToMany
    {
        return $this->belongsToMany(ProgramTerm::class, 'program_term_user')->withTimestamps();
    }

    /** @return BelongsToMany<HostEstablishment, $this> */
    public function hostEstablishments(): BelongsToMany
    {
        return $this->belongsToMany(HostEstablishment::class, 'host_establishment_user')->withTimestamps();
    }

    /** @return HasMany<Placement, $this> */
    public function supervisedPlacements(): HasMany
    {
        return $this->hasMany(Placement::class, 'supervisor_id');
    }

    /** @return HasMany<PlacementDecision, $this> */
    public function placementDecisions(): HasMany
    {
        return $this->hasMany(PlacementDecision::class, 'decided_by');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /** @return HasMany<RequirementReview, $this> */
    public function requirementReviews(): HasMany
    {
        return $this->hasMany(RequirementReview::class, 'reviewed_by');
    }

    /** @return HasMany<Evaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'evaluator_id');
    }

    /** @return HasMany<TimeLog, $this> */
    public function verifiedTimeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class, 'verified_by');
    }
}
