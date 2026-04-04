<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => false,
            'color' => $this->faker->optional()->hexColor(),
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }
}
