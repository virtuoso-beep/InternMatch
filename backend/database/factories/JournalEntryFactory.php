<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\JournalEntry;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'week_starts_on' => '2026-08-03',
            'content' => fake()->paragraph(),
            'status' => SubmissionStatus::Draft,
        ];
    }
}
