<?php

namespace Database\Factories;

use App\AccountStatus;
use App\Models\Placement;
use App\Models\PlacementDecision;
use App\Models\User;
use App\PlacementDecisionType;
use App\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlacementDecision> */
class PlacementDecisionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'decided_by' => User::factory()->state(['role' => Role::Coordinator, 'status' => AccountStatus::Active]),
            'decision' => PlacementDecisionType::Approve,
            'reason' => 'Reviewed placement suitability and agreement coverage.',
            'decided_at' => '2026-08-03 10:00:00',
        ];
    }
}
