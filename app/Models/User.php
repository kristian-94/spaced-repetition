<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_chat_id',
        'daily_new_cards_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'daily_new_cards_limit' => 'integer',
        ];
    }

    public function decks()
    {
        return $this->hasMany(Deck::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function reviewLogs()
    {
        return $this->hasMany(ReviewLog::class);
    }
}
