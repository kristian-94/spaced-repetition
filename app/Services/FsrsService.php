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
     * Get the count of due cards for a deck
     */
    public function getDueCount(Deck $deck): int
    {
        return $deck->cards()->dueToday()->count();
    }

    /**
     * Get the next card to review for a deck (ordered by due date ascending)
     */
    public function getNextCard(Deck $deck): ?Card
    {
        return $deck->cards()
            ->dueToday()
            ->orderBy('fsrs_due', 'asc')
            ->first();
    }
}
