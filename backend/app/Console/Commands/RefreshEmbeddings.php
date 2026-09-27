<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use App\Models\Student;
use App\Services\SemanticEmbeddings;
use Illuminate\Console\Command;

class RefreshEmbeddings extends Command
{
    protected $signature = 'internmatch:refresh-embeddings';
    protected $description = 'Precompute current student and opportunity embeddings; reuse matching provenance';

    public function handle(SemanticEmbeddings $embeddings): int
    {
        foreach ([Student::class, Opportunity::class] as $model) {
            foreach ($model::orderBy('id')->lazyById() as $owner) { $embeddings->refresh($owner); }
        }
        $this->info('Embedding cache is current.');
        return self::SUCCESS;
    }
}
