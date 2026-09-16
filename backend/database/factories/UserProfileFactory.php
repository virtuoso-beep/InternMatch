<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserProfile> */
class UserProfileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contact_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'bio' => fake()->sentence(),
        ];
    }
}
