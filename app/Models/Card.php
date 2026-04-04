<?php

namespace App\Models;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Scottlaurent\FSRS\Card as FsrsCard;
use Scottlaurent\FSRS\State;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'deck_id',
        'user_id',
        'front_content',
        'back_content',
        'front_image',
        'back_image',
        'card_type',
        'is_suspended',
        'fsrs_due',
        'fsrs_state',
        'fsrs_stability',
        'fsrs_difficulty',
        'fsrs_reps',
        'fsrs_lapses',
        'fsrs_scheduled_days',
        'fsrs_elapsed_days',
        'fsrs_step',
        'fsrs_last_review',
    ];

    protected $casts = [
        'is_suspended' => 'boolean',
        'fsrs_due' => 'datetime',
        'fsrs_stability' => 'float',
        'fsrs_difficulty' => 'float',
        'fsrs_reps' => 'integer',
        'fsrs_lapses' => 'integer',
        'fsrs_scheduled_days' => 'integer',
        'fsrs_elapsed_days' => 'integer',
        'fsrs_step' => 'integer',
        'fsrs_last_review' => 'datetime',
    ];

    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewLogs()
    {
        return $this->hasMany(ReviewLog::class);
    }

    public function scopeDueToday($query)
    {
        return $query->where('fsrs_due', '<=', now())->where('is_suspended', false);
    }

    /**
     * Convert this card to an FSRS Card object for scheduling
     */
    public function toFsrsCard(): FsrsCard
    {
        $due = $this->fsrs_due
            ? new DateTime($this->fsrs_due->format('c'), new DateTimeZone('UTC'))
            : new DateTime('now', new DateTimeZone('UTC'));

        return FsrsCard::fromArray([
            'cardId' => (string) $this->id,
            'due' => $due->format('c'),
            'stability' => $this->fsrs_stability ?? 0,
            'difficulty' => $this->fsrs_difficulty ?? 0,
            'elapsedDays' => $this->fsrs_elapsed_days ?? 0,
            'scheduledDays' => $this->fsrs_scheduled_days ?? 0,
            'reps' => $this->fsrs_reps ?? 0,
            'lapses' => $this->fsrs_lapses ?? 0,
            'state' => $this->fsrs_state ?? State::NEW,
            'step' => $this->fsrs_step ?? 0,
            'lastReview' => $this->fsrs_last_review
                ? $this->fsrs_last_review->format('c')
                : null,
            'retrievability' => null,
        ]);
    }

    /**
     * Update this card's FSRS fields from an FSRS Card object
     */
    public function updateFromFsrsCard(FsrsCard $fsrsCard): void
    {
        $this->fsrs_due = $fsrsCard->due ? $fsrsCard->due->format('Y-m-d H:i:s') : now();
        $this->fsrs_state = $fsrsCard->state;
        $this->fsrs_stability = $fsrsCard->stability;
        $this->fsrs_difficulty = $fsrsCard->difficulty;
        $this->fsrs_reps = $fsrsCard->reps;
        $this->fsrs_lapses = $fsrsCard->lapses;
        $this->fsrs_scheduled_days = $fsrsCard->scheduledDays;
        $this->fsrs_elapsed_days = $fsrsCard->elapsedDays;
        $this->fsrs_step = $fsrsCard->step;
        $this->fsrs_last_review = $fsrsCard->lastReview
            ? $fsrsCard->lastReview->format('Y-m-d H:i:s')
            : now();
        $this->save();
    }
}
