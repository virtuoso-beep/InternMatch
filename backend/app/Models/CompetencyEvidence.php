<?php

namespace App\Models;

use Database\Factories\CompetencyEvidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_competency_id', 'document_id', 'source', 'description', 'external_url'])]
class CompetencyEvidence extends Model
{
    /** @use HasFactory<CompetencyEvidenceFactory> */
    use HasFactory;

    protected $table = 'competency_evidence';

    /** @return BelongsTo<StudentCompetency, $this> */
    public function studentCompetency(): BelongsTo
    {
        return $this->belongsTo(StudentCompetency::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
