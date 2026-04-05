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
     * Get the count of due cards for a deck, respecting the global daily new card limit.
     */
    public function getDueCount(Deck $deck): int
    {
        $reviewDue = $deck->cards()->dueToday()->where('fsrs_state', '>', 0)->count();

        $limit = $deck->user->daily_new_cards_limit ?: 20;
        $newSeenToday = $this->newCardsSeenTodayForUser($deck->user_id);
        $newDue = min(
            max(0, $limit - $newSeenToday),
            $deck->cards()->dueToday()->where('fsrs_state', 0)->count()
        );

        return $reviewDue + $newDue;
    }

    /**
     * Get the next card to review for a deck, respecting the global daily new card limit.
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

        // New cards: respect the global daily limit across all decks
        $limit = $deck->user->daily_new_cards_limit ?: 20;
        if ($this->newCardsSeenTodayForUser($deck->user_id) >= $limit) {
            return null;
        }

        return $deck->cards()
            ->dueToday()
            ->where('fsrs_state', 0)
            ->orderBy('fsrs_due', 'asc')
            ->first();
    }

    private function newCardsSeenTodayForUser(int $userId): int
    {
        $sydney     = 'Australia/Sydney';
        $todayStart = now($sydney)->startOfDay()->utc();
        $todayEnd   = now($sydney)->endOfDay()->utc();

        return ReviewLog::where('user_id', $userId)
            ->whereBetween('reviewed_at', [$todayStart, $todayEnd])
            ->where('state_before', 0)
            ->distinct('card_id')
            ->count('card_id');
    }
}
