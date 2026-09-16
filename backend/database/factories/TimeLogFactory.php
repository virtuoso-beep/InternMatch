<?php

namespace Database\Factories;

use App\Enums\TimeLogStatus;
use App\Models\Placement;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimeLog> */
class TimeLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'work_date' => '2026-08-04',
            'time_in' => '2026-08-04 08:00:00',
            'time_out' => '2026-08-04 17:00:00',
            'break_minutes' => 60,
            'status' => TimeLogStatus::Pending,
        ];
    }
}
