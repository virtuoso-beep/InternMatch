<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256'])]
#[Hidden(['disk', 'path'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<CompetencyEvidence, $this> */
    public function competencyEvidence(): HasMany
    {
        return $this->hasMany(CompetencyEvidence::class);
    }

    /** @return HasMany<RequirementSubmission, $this> */
    public function requirementSubmissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class);
    }

    /** @return HasMany<Moa, $this> */
    public function moas(): HasMany
    {
        return $this->hasMany(Moa::class);
    }

    /** @return HasMany<JournalEntry, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
