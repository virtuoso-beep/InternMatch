<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\Student;
use App\Services\SemanticEmbeddings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshSemanticEmbedding implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 100;

    public function __construct(public string $kind, public int $id) {}

    public function backoff(): array { return [30, 120, 300]; }

    public function handle(SemanticEmbeddings $embeddings): void
    {
        $owner = match ($this->kind) {
            'student' => Student::find($this->id),
            'opportunity' => Opportunity::find($this->id),
            default => null,
        };
        if ($owner) { $embeddings->refresh($owner); }
    }
}
