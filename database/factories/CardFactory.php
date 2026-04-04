<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'user_id' => User::factory(),
            'front_content' => $this->faker->sentence(),
            'back_content' => $this->faker->sentence(),
            'front_image' => null,
            'back_image' => null,
            'card_type' => 'basic',
            'is_suspended' => false,
            'fsrs_due' => now(),
            'fsrs_state' => 0,
            'fsrs_stability' => null,
            'fsrs_difficulty' => null,
            'fsrs_reps' => 0,
            'fsrs_lapses' => 0,
            'fsrs_scheduled_days' => 0,
            'fsrs_elapsed_days' => 0,
            'fsrs_step' => null,
            'fsrs_last_review' => null,
        ];
    }

    public function due(): static
    {
        return $this->state(['fsrs_due' => now()->subMinute(), 'is_suspended' => false]);
    }

    public function notDue(): static
    {
        return $this->state(['fsrs_due' => now()->addDays(5)]);
    }

    public function suspended(): static
    {
        return $this->state(['is_suspended' => true]);
    }
}
