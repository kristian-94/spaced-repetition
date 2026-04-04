<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a message to user's telegram chat
     */
    public function send(User $user, string $message): void
    {
        if (!$user->telegram_chat_id) {
            return;
        }

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            Log::warning('Telegram bot token not configured');
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $user->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram send failed: ' . $e->getMessage());
        }
    }
}
