<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\ReviewLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_index_requires_auth(): void
    {
        $deck = Deck::factory()->create();

        $this->get(route('review.index', $deck))->assertRedirect(route('login'));
    }

    public function test_review_index_renders_with_due_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        Card::factory()->for($deck)->for($user)->due()->create();

        $this->actingAs($user)
            ->get(route('review.index', $deck))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Review/Index')
                ->has('card')
                ->where('dueCount', 1)
            );
    }

    public function test_review_index_shows_no_card_when_none_due(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        Card::factory()->for($deck)->for($user)->notDue()->create();

        $this->actingAs($user)
            ->get(route('review.index', $deck))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Review/Index')
                ->where('card', null)
                ->where('dueCount', 0)
            );
    }

    public function test_review_index_does_not_show_suspended_cards(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        Card::factory()->for($deck)->for($user)->due()->suspended()->create();

        $this->actingAs($user)
            ->get(route('review.index', $deck))
            ->assertInertia(fn ($page) => $page
                ->where('card', null)
                ->where('dueCount', 0)
            );
    }

    public function test_review_index_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $deck = Deck::factory()->for($other)->create();

        $this->actingAs($user)
            ->get(route('review.index', $deck))
            ->assertForbidden();
    }

    public function test_submit_review_with_good_rating(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 3, // Good
            ])
            ->assertRedirect(route('review.index', $deck));
    }

    public function test_submit_review_creates_review_log(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 3,
            ]);

        $this->assertDatabaseHas('review_logs', [
            'card_id' => $card->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);
    }

    public function test_submit_review_updates_card_fsrs_fields(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create([
            'fsrs_reps' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 3,
            ]);

        $updated = $card->fresh();
        $this->assertGreaterThan(0, $updated->fsrs_reps);
        $this->assertNotNull($updated->fsrs_stability);
    }

    public function test_submit_review_with_again_increments_lapses(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        // Graduate the card with two Good ratings so it has a lastReview set
        $this->actingAs($user)->post(route('review.submit', $deck), ['card_id' => $card->id, 'rating' => 3]);
        $card->refresh();
        $card->fsrs_due = now()->subMinute();
        $card->save();
        $this->actingAs($user)->post(route('review.submit', $deck), ['card_id' => $card->id, 'rating' => 3]);
        $card->refresh();
        $card->fsrs_due = now()->subMinute();
        $card->save();

        $lapsesBefore = $card->fsrs_lapses;

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 1, // Again
            ]);

        $this->assertGreaterThan($lapsesBefore, $card->fresh()->fsrs_lapses);
    }

    public function test_submit_review_validates_rating_range(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 5, // Invalid — must be 1-4
            ])
            ->assertSessionHasErrors('rating');

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 0, // Invalid
            ])
            ->assertSessionHasErrors('rating');
    }

    public function test_submit_review_accepts_optional_duration(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $this->actingAs($user)
            ->post(route('review.submit', $deck), [
                'card_id' => $card->id,
                'rating' => 4,
                'duration_ms' => 3500,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('review_logs', [
            'card_id' => $card->id,
            'review_duration_ms' => 3500,
        ]);
    }

    public function test_review_log_count_increases_after_all_ratings(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        foreach ([1, 2, 3, 4] as $rating) {
            $card = Card::factory()->for($deck)->for($user)->due()->create();

            $this->actingAs($user)
                ->post(route('review.submit', $deck), [
                    'card_id' => $card->id,
                    'rating' => $rating,
                ]);
        }

        $this->assertDatabaseCount('review_logs', 4);
    }
}
