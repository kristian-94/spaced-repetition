<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'user_id',
        'deck_id',
        'rating',
        'state_before',
        'state_after',
        'scheduled_days',
        'elapsed_days',
        'review_duration_ms',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'rating' => 'integer',
        'state_before' => 'integer',
        'state_after' => 'integer',
        'scheduled_days' => 'integer',
        'elapsed_days' => 'integer',
        'review_duration_ms' => 'integer',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }
}
