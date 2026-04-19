<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeckDailyBoost extends Model
{
    protected $fillable = ['deck_id', 'date', 'extra_cards'];

    protected $casts = [
        'extra_cards' => 'integer',
    ];

    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }
}
