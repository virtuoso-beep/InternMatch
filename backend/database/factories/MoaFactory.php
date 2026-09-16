<?php

namespace Database\Factories;

use App\MoaStatus;
use App\Models\HostEstablishment;
use App\Models\Moa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Moa> */
class MoaFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'host_establishment_id' => HostEstablishment::factory(),
            'reference_number' => fake()->unique()->bothify('MOA-########'),
            'status' => MoaStatus::Active,
            'effective_on' => '2026-01-01',
            'expires_on' => '2027-12-31',
        ];
    }
}
