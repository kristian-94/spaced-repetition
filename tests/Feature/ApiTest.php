<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    // --- Auth ---

    public function test_api_requires_sanctum_token(): void
    {
        $this->getJson('/api/decks')->assertUnauthorized();
        $this->postJson('/api/decks', ['name' => 'Test'])->assertUnauthorized();
    }

    // --- List decks ---

    public function test_api_list_decks_returns_only_own_decks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Deck::factory()->for($user)->create(['name' => 'My Deck']);
        Deck::factory()->for($other)->create(['name' => 'Other Deck']);

        Sanctum::actingAs($user, ['api']);

        $this->getJson('/api/decks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'My Deck');
    }

    // --- Create deck ---

    public function test_api_can_create_deck(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api']);

        $this->postJson('/api/decks', ['name' => 'Spanish Vocab', 'description' => 'Common words'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Spanish Vocab');

        $this->assertDatabaseHas('decks', ['user_id' => $user->id, 'name' => 'Spanish Vocab']);
    }

    public function test_api_create_deck_requires_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api']);

        $this->postJson('/api/decks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    // --- List cards ---

    public function test_api_list_cards_returns_paginated_cards(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        Card::factory()->for($deck)->for($user)->count(3)->create();

        Sanctum::actingAs($user, ['api']);

        $this->getJson("/api/decks/{$deck->id}/cards")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_list_cards_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        Sanctum::actingAs($user, ['api']);

        $this->getJson("/api/decks/{$deck->id}/cards")
            ->assertForbidden();
    }

    // --- Create single card ---

    public function test_api_can_create_single_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/decks/{$deck->id}/cards", [
            'front' => 'Bonjour',
            'back' => 'Hello',
        ])
            ->assertCreated()
            ->assertJsonPath('count', 1);

        $this->assertDatabaseHas('cards', [
            'deck_id' => $deck->id,
            'front_content' => 'Bonjour',
            'back_content' => 'Hello',
        ]);
    }

    public function test_api_can_create_single_card_using_content_keys(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/decks/{$deck->id}/cards", [
            'front_content' => 'Merci',
            'back_content' => 'Thank you',
        ])
            ->assertCreated()
            ->assertJsonPath('count', 1);
    }

    // --- Create batch cards ---

    public function test_api_can_create_batch_cards(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $payload = [
            ['front' => 'Un', 'back' => 'One'],
            ['front' => 'Deux', 'back' => 'Two'],
            ['front' => 'Trois', 'back' => 'Three'],
        ];

        $this->postJson("/api/decks/{$deck->id}/cards", $payload)
            ->assertCreated()
            ->assertJsonPath('count', 3);

        $this->assertDatabaseCount('cards', 3);
    }

    public function test_api_can_create_batch_cards_with_cards_wrapper(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/decks/{$deck->id}/cards", [
            'cards' => [
                ['front_content' => 'Un', 'back_content' => 'One'],
                ['front_content' => 'Deux', 'back_content' => 'Two'],
                ['front_content' => 'Trois', 'back_content' => 'Three'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('count', 3);

        $this->assertDatabaseCount('cards', 3);
    }

    public function test_api_create_cards_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/decks/{$deck->id}/cards", [
            'front' => 'Test',
            'back' => 'Test',
        ])->assertForbidden();
    }

    public function test_api_new_card_is_due_immediately(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/decks/{$deck->id}/cards", [
            'front' => 'Q',
            'back' => 'A',
        ])->assertCreated();

        $card = Card::where('deck_id', $deck->id)->first();
        $this->assertLessThanOrEqual(now(), $card->fsrs_due);
    }

    // --- Update card ---

    public function test_api_can_update_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->putJson("/api/cards/{$card->id}", [
            'front_content' => 'Updated front',
            'back_content' => 'Updated back',
        ])
            ->assertOk()
            ->assertJsonPath('data.front_content', 'Updated front');
    }

    public function test_api_cannot_update_another_users_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();
        $card = Card::factory()->for($deck)->for($other)->create();

        Sanctum::actingAs($user, ['api']);

        $this->putJson("/api/cards/{$card->id}", [
            'front_content' => 'Hacked',
            'back_content' => 'Hacked',
        ])->assertForbidden();
    }

    // --- Delete card ---

    public function test_api_can_delete_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create();

        Sanctum::actingAs($user, ['api']);

        $this->deleteJson("/api/cards/{$card->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Card deleted.');

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_api_cannot_delete_another_users_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();
        $card = Card::factory()->for($deck)->for($other)->create();

        Sanctum::actingAs($user, ['api']);

        $this->deleteJson("/api/cards/{$card->id}")->assertForbidden();
    }

    // --- Suspend card ---

    public function test_api_can_suspend_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create(['is_suspended' => false]);

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/cards/{$card->id}/suspend")
            ->assertOk();

        $this->assertTrue($card->fresh()->is_suspended);
    }

    public function test_api_cannot_suspend_another_users_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();
        $card = Card::factory()->for($deck)->for($other)->create();

        Sanctum::actingAs($user, ['api']);

        $this->postJson("/api/cards/{$card->id}/suspend")->assertForbidden();
    }
}
