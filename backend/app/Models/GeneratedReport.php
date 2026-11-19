<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['payload' => 'array', 'contains_synthetic' => 'boolean', 'approved_at' => 'immutable_datetime']; }

    public function programTerm(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(ProgramTerm::class); }

    protected static function booted(): void
    {
        static::updating(function (self $report): void {
            if ($report->isDirty(['program_term_id', 'kind', 'payload', 'contains_synthetic', 'content_hash', 'generated_by'])) {
                throw new \LogicException('Report snapshots are immutable; generate a new report.');
            }
        });
    }
}
