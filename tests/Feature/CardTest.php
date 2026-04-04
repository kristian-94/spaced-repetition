<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_index_requires_auth(): void
    {
        $deck = Deck::factory()->create();

        $this->get(route('cards.index', $deck))->assertRedirect(route('login'));
    }

    public function test_card_index_renders_for_deck_owner(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('cards.index', $deck))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Decks/Show'));
    }

    public function test_card_index_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        $this->actingAs($user)
            ->get(route('cards.index', $deck))
            ->assertForbidden();
    }

    public function test_user_can_create_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('cards.store', $deck), [
                'front_content' => 'What is the capital of France?',
                'back_content' => 'Paris',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cards', [
            'deck_id' => $deck->id,
            'front_content' => 'What is the capital of France?',
            'back_content' => 'Paris',
        ]);
    }

    public function test_card_creation_requires_front_and_back(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('cards.store', $deck), ['front_content' => 'Only front'])
            ->assertSessionHasErrors('back_content');

        $this->actingAs($user)
            ->post(route('cards.store', $deck), ['back_content' => 'Only back'])
            ->assertSessionHasErrors('front_content');
    }

    public function test_user_can_create_card_with_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $file = UploadedFile::fake()->image('card.jpg');

        $this->actingAs($user)
            ->post(route('cards.store', $deck), [
                'front_content' => 'Front with image',
                'back_content' => 'Back',
                'front_image' => $file,
            ])
            ->assertRedirect();

        $card = Card::where('deck_id', $deck->id)->first();
        $this->assertNotNull($card->front_image);
        Storage::disk('public')->assertExists($card->front_image);
    }

    public function test_user_can_update_own_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create();

        $this->actingAs($user)
            ->patch(route('cards.update', $card), [
                'front_content' => 'Updated front',
                'back_content' => 'Updated back',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'front_content' => 'Updated front',
        ]);
    }

    public function test_user_cannot_update_another_users_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();
        $card = Card::factory()->for($deck)->for($other)->create();

        $this->actingAs($user)
            ->patch(route('cards.update', $card), [
                'front_content' => 'Hacked',
                'back_content' => 'Hacked',
            ])
            ->assertForbidden();
    }

    public function test_user_can_delete_own_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create();

        $this->actingAs($user)
            ->delete(route('cards.destroy', $card))
            ->assertRedirect();

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_user_cannot_delete_another_users_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();
        $card = Card::factory()->for($deck)->for($other)->create();

        $this->actingAs($user)
            ->delete(route('cards.destroy', $card))
            ->assertForbidden();
    }

    public function test_suspend_toggles_card_suspended_state(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->create(['is_suspended' => false]);

        $this->actingAs($user)
            ->patch(route('cards.suspend', $card))
            ->assertRedirect();

        $this->assertTrue($card->fresh()->is_suspended);

        $this->actingAs($user)
            ->patch(route('cards.suspend', $card))
            ->assertRedirect();

        $this->assertFalse($card->fresh()->is_suspended);
    }

    public function test_new_card_is_due_immediately(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('cards.store', $deck), [
                'front_content' => 'Q',
                'back_content' => 'A',
            ]);

        $card = Card::where('deck_id', $deck->id)->first();
        $this->assertLessThanOrEqual(now(), $card->fsrs_due);
    }
}
