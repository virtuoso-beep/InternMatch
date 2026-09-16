<?php

namespace Database\Factories;

use App\Models\MonitoringFlag;
use App\Models\Placement;
use App\RiskSeverity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MonitoringFlag> */
class MonitoringFlagFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'code' => 'hours_behind_schedule',
            'severity' => RiskSeverity::Warning,
            'description' => 'Coordinator flagged progress for follow-up.',
            'raised_at' => '2026-08-05 09:00:00',
        ];
    }
}
