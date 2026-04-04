<?php

namespace Tests\Unit;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use App\Services\FsrsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FsrsServiceTest extends TestCase
{
    use RefreshDatabase;

    private FsrsService $fsrs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fsrs = new FsrsService();
    }

    public function test_review_updates_card_and_returns_log(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $result = $this->fsrs->review($card, 3);

        $this->assertArrayHasKey('card', $result);
        $this->assertArrayHasKey('log', $result);
        $this->assertInstanceOf(Card::class, $result['card']);
    }

    public function test_review_increments_reps_on_good_rating(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create(['fsrs_reps' => 0]);

        $this->fsrs->review($card, 3);

        $this->assertGreaterThan(0, $card->fresh()->fsrs_reps);
    }

    public function test_review_increments_lapses_on_again_rating(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        // Graduate the card through learning phase with Good ratings
        $this->fsrs->review($card, 3);
        $card->refresh();
        $card->fsrs_due = now()->subMinute();
        $card->save();

        $this->fsrs->review($card, 3);
        $card->refresh();
        $card->fsrs_due = now()->subMinute();
        $card->save();

        $lapsesBefore = $card->fsrs_lapses;

        // Now rate Again on a card that has been reviewed before
        $this->fsrs->review($card, 1); // Again

        $this->assertGreaterThan($lapsesBefore, $card->fresh()->fsrs_lapses);
    }

    public function test_review_persists_log_to_database(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $card = Card::factory()->for($deck)->for($user)->due()->create();

        $this->fsrs->review($card, 4);

        $this->assertDatabaseHas('review_logs', [
            'card_id' => $card->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);
    }

    public function test_get_due_count_returns_correct_count(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Card::factory()->for($deck)->for($user)->due()->count(3)->create();
        Card::factory()->for($deck)->for($user)->notDue()->count(2)->create();

        $this->assertSame(3, $this->fsrs->getDueCount($deck));
    }

    public function test_get_due_count_excludes_suspended_cards(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        Card::factory()->for($deck)->for($user)->due()->count(2)->create();
        Card::factory()->for($deck)->for($user)->due()->suspended()->count(1)->create();

        $this->assertSame(2, $this->fsrs->getDueCount($deck));
    }

    public function test_get_next_card_returns_most_overdue_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();

        $newer = Card::factory()->for($deck)->for($user)->create([
            'fsrs_due' => now()->subHour(),
            'front_content' => 'Newer overdue',
        ]);
        $older = Card::factory()->for($deck)->for($user)->create([
            'fsrs_due' => now()->subDays(3),
            'front_content' => 'Older overdue',
        ]);

        $next = $this->fsrs->getNextCard($deck);

        $this->assertEquals($older->id, $next->id);
    }

    public function test_get_next_card_returns_null_when_none_due(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        Card::factory()->for($deck)->for($user)->notDue()->create();

        $this->assertNull($this->fsrs->getNextCard($deck));
    }

    public function test_review_with_easy_schedules_longer_interval(): void
    {
        $user = User::factory()->create();
        $deck = Deck::factory()->for($user)->create();
        $cardGood = Card::factory()->for($deck)->for($user)->due()->create();
        $cardEasy = Card::factory()->for($deck)->for($user)->due()->create();

        $this->fsrs->review($cardGood, 3); // Good
        $this->fsrs->review($cardEasy, 4); // Easy

        $this->assertGreaterThan(
            $cardGood->fresh()->fsrs_scheduled_days,
            $cardEasy->fresh()->fsrs_scheduled_days
        );
    }
}
