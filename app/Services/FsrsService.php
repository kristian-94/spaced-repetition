<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Deck;
use App\Models\ReviewLog;
use DateTime;
use DateTimeZone;
use Scottlaurent\FSRS\Manager;

class FsrsService
{
    private Manager $fsrs;

    public function __construct()
    {
        $this->fsrs = new Manager(defaultRequestRetention: 0.9);
    }

    /**
     * Schedule a card review - returns next card state and persisted review log
     *
     * @return array{'card': Card, 'log': ReviewLog}
     */
    public function review(Card $card, int $rating, ?int $durationMs = null): array
    {
        $stateBefore = $card->fsrs_state;
        $fsrsCard = $card->toFsrsCard();

        $reviewDate = new DateTime('now', new DateTimeZone('UTC'));
        $result = $this->fsrs->reviewCard($fsrsCard, $rating, $reviewDate, $durationMs);

        $updatedFsrsCard = $result['card'];
        $card->updateFromFsrsCard($updatedFsrsCard);

        $log = ReviewLog::create([
            'card_id' => $card->id,
            'user_id' => $card->user_id,
            'deck_id' => $card->deck_id,
            'rating' => $rating,
            'state_before' => $stateBefore,
            'state_after' => $updatedFsrsCard->state,
            'scheduled_days' => $updatedFsrsCard->scheduledDays,
            'elapsed_days' => $updatedFsrsCard->elapsedDays,
            'review_duration_ms' => $durationMs,
            'reviewed_at' => $reviewDate->format('Y-m-d H:i:s'),
        ]);

        return [
            'card' => $card,
            'log' => $log,
        ];
    }

    /**
     * Get review and new due counts separately for a deck.
     *
     * @return array{'review': int, 'new': int}
     */
    public function getDueCounts(Deck $deck): array
    {
        $reviewDue = $deck->cards()->dueToday()->where('fsrs_state', '>', 0)->count();

        $perDeckLimit = $this->perDeckNewCardLimit($deck);
        $newSeenTodayForDeck = $this->newCardsSeenTodayForDeck($deck->id, $deck->user_id);
        $newDue = min(
            max(0, $perDeckLimit - $newSeenTodayForDeck),
            $deck->cards()->dueToday()->where('fsrs_state', 0)->count()
        );

        return ['review' => $reviewDue, 'new' => $newDue];
    }

    /**
     * Get the total count of due cards for a deck.
     */
    public function getDueCount(Deck $deck): int
    {
        ['review' => $reviewDue, 'new' => $newDue] = $this->getDueCounts($deck);
        return $reviewDue + $newDue;
    }

    /**
     * Get the next card to review for a deck, respecting the per-deck share of the daily new card limit.
     * Already-seen cards (state > 0) that are due are always shown first.
     */
    public function getNextCard(Deck $deck): ?Card
    {
        // Prefer cards already in learning/review (state > 0)
        $reviewCard = $deck->cards()
            ->dueToday()
            ->where('fsrs_state', '>', 0)
            ->orderBy('fsrs_due', 'asc')
            ->first();

        if ($reviewCard) {
            return $reviewCard;
        }

        // New cards: each deck gets an equal share of the daily limit
        $perDeckLimit = $this->perDeckNewCardLimit($deck);
        if ($this->newCardsSeenTodayForDeck($deck->id, $deck->user_id) >= $perDeckLimit) {
            return null;
        }

        return $deck->cards()
            ->dueToday()
            ->where('fsrs_state', 0)
            ->orderBy('fsrs_due', 'asc')
            ->first();
    }

    /**
     * Calculate the per-deck new card limit by dividing the global limit evenly across active decks.
     */
    private function perDeckNewCardLimit(Deck $deck): int
    {
        $globalLimit = $deck->user->daily_new_cards_limit ?: 20;
        $deckCount = $deck->user->decks()->active()->count();

        return (int) floor($globalLimit / max(1, $deckCount));
    }

    private function newCardsSeenTodayForDeck(int $deckId, int $userId): int
    {
        $sydney     = 'Australia/Sydney';
        $todayStart = now($sydney)->startOfDay()->utc();
        $todayEnd   = now($sydney)->endOfDay()->utc();

        return ReviewLog::where('user_id', $userId)
            ->where('deck_id', $deckId)
            ->whereBetween('reviewed_at', [$todayStart, $todayEnd])
            ->where('state_before', 0)
            ->distinct('card_id')
            ->count('card_id');
    }
}
