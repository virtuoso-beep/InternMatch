<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['placement_id', 'week_starts_on', 'content', 'document_id', 'status', 'submitted_at'])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'week_starts_on' => 'immutable_date',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Placement, $this> */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
