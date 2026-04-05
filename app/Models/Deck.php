<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'color',
        'new_cards_per_day',
        'tts_language',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'new_cards_per_day' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function reviewLogs()
    {
        return $this->hasMany(ReviewLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
