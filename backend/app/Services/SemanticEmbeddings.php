<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SemanticEmbeddings
{
    public function source(Student|Opportunity $owner): string
    {
        if ($owner instanceof Student) {
            return mb_substr($owner->studentCompetencies()->with('competency')->orderBy('competency_id')->get()
                ->map(fn ($skill) => $skill->competency->name)->implode('; '), 0, 12000);
        }

        return trim(mb_substr(implode("\n", [$owner->title, $owner->description, $owner->tasks,
            $owner->competencies()->orderBy('competencies.id')->pluck('name')->implode('; ')]), 0, 12000));
    }

    public function key(Student|Opportunity $owner): array
    {
        return ['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->id,
            'source_text_hash' => hash('sha256', ($owner instanceof Student ? 'query: ' : 'passage: ').$this->source($owner)),
            'model_name' => config('matching.model'), 'model_version' => config('matching.revision')];
    }

    public function cached(Student|Opportunity $owner): ?object
    {
        if ($this->source($owner) === '') {
            return null;
        }

        return DB::table('embeddings')->where($this->key($owner))->first();
    }

    public function refresh(Student|Opportunity $owner): void
    {
        $source = $this->source($owner);
        if ($source === '' || $this->cached($owner)) {
            return;
        }
        $key = $this->key($owner);
        $result = Http::connectTimeout(5)->timeout(90)->post(rtrim(config('matching.url'), '/').'/embeddings', [
            'kind' => $owner instanceof Student ? 'student' : 'opportunity', 'text' => $source,
        ])->throw()->json();
        $vector = $result['vector'] ?? [];
        if (($result['source_text_hash'] ?? null) !== $key['source_text_hash']
            || ($result['model_name'] ?? null) !== $key['model_name'] || ($result['model_version'] ?? null) !== $key['model_version']
            || ! is_array($vector) || count($vector) !== 768 || collect($vector)->contains(fn ($v) => ! is_numeric($v) || ! is_finite((float) $v))
            || array_sum(array_map(fn ($v) => $v * $v, $vector)) <= 0) {
            throw new RuntimeException('AI service returned invalid embedding provenance or vector.');
        }
        // Old hashes remain auditable. Retrieval only selects the current source hash.
        DB::table('embeddings')->insertOrIgnore($key + ['vector' => json_encode($vector, JSON_THROW_ON_ERROR), 'generated_at' => now()]);
    }
}
