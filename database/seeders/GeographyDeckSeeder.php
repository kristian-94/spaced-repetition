<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeographyDeckSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::orderBy('id')->first();

        if (! $user) {
            $this->command->error('No users found. Create a user first.');
            return;
        }

        $jsonPath = base_path('scripts/geography_deck.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("geography_deck.json not found at {$jsonPath}. Run: uv run scripts/generate_geography_deck.py");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        $deck = Deck::where('name', $data['deck']['name'])
            ->where('user_id', $user->id)
            ->first();

        if ($deck) {
            $this->command->info("Deck already exists (id={$deck->id}), replacing cards...");
            $deck->cards()->delete();
        } else {
            $deck = Deck::create([
                'user_id'          => $user->id,
                'name'             => $data['deck']['name'],
                'description'      => $data['deck']['description'],
                'is_active'        => $data['deck']['is_active'],
                'new_cards_per_day' => $data['deck']['new_cards_per_day'],
            ]);
            $this->command->info("Created deck: {$deck->name} (id={$deck->id})");
        }

        $now = now();
        $rows = array_map(fn($card) => [
            'deck_id'             => $deck->id,
            'user_id'             => $user->id,
            'front_content'       => $card['front_content'],
            'back_content'        => $card['back_content'],
            'front_image_url'     => $card['front_image_url'],
            'card_type'           => 'basic',
            'is_suspended'        => false,
            'fsrs_due'            => $now,
            'fsrs_state'          => 0,
            'fsrs_reps'           => 0,
            'fsrs_lapses'         => 0,
            'fsrs_scheduled_days' => 0,
            'fsrs_elapsed_days'   => 0,
            'created_at'          => $now,
            'updated_at'          => $now,
        ], $data['cards']);

        foreach (array_chunk($rows, 100) as $chunk) {
            Card::insert($chunk);
        }

        $this->command->info('Inserted ' . count($rows) . ' cards.');
    }
}
