<?php

namespace Database\Factories;

use App\Models\HostEstablishment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HostEstablishment> */
class HostEstablishmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('HTE-######'),
            'name' => fake()->company(),
            'industry' => 'Information Technology',
            'address' => fake()->streetAddress(),
            'city' => 'Tagum City',
            'is_active' => true,
        ];
    }
}
