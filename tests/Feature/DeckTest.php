<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeckTest extends TestCase
{
    use RefreshDatabase;

    public function test_decks_index_requires_auth(): void
    {
        $this->get(route('decks.index'))->assertRedirect(route('login'));
    }

    public function test_decks_index_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('decks.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Decks/Index'));
    }

    public function test_decks_index_only_shows_own_decks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Deck::factory()->for($user)->create(['name' => 'My Deck']);
        Deck::factory()->for($other)->create(['name' => 'Other Deck']);

        $this->actingAs($user)
            ->get(route('decks.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Decks/Index')
                ->where('decks.0.name', 'My Deck')
                ->count('decks', 1)
            );
    }

    public function test_user_can_create_deck(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('decks.store'), ['name' => 'French Vocab', 'description' => 'French words'])
            ->assertRedirect(route('decks.index'));

        $this->assertDatabaseHas('decks', [
            'user_id' => $user->id,
            'name' => 'French Vocab',
        ]);
    }

    public function test_deck_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('decks.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_user_can_update_own_deck(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->patch(route('decks.update', $deck), ['name' => 'New Name'])
            ->assertRedirect();

        $this->assertDatabaseHas('decks', ['id' => $deck->id, 'name' => 'New Name']);
    }

    public function test_user_cannot_update_another_users_deck(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        $this->actingAs($user)
            ->patch(route('decks.update', $deck), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_user_can_delete_own_deck(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('decks.destroy', $deck))
            ->assertRedirect(route('decks.index'));

        $this->assertDatabaseMissing('decks', ['id' => $deck->id]);
    }

    public function test_user_cannot_delete_another_users_deck(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        $this->actingAs($user)
            ->delete(route('decks.destroy', $deck))
            ->assertForbidden();
    }

    public function test_toggle_active_flips_is_active(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create(['is_active' => false]);

        $this->actingAs($user)
            ->patch(route('decks.toggleActive', $deck))
            ->assertRedirect();

        $this->assertTrue($deck->fresh()->is_active);

        $this->actingAs($user)
            ->patch(route('decks.toggleActive', $deck))
            ->assertRedirect();

        $this->assertFalse($deck->fresh()->is_active);
    }
}
