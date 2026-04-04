<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Handle incoming Telegram webhook (for setup / getting chat IDs)
     */
    public function telegramWebhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram webhook received', $update);

        // Extract chat ID from message
        $chatId = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? '';

        if ($chatId && str_starts_with($text, '/start')) {
            // Log the chat ID so users can find it
            Log::info("Telegram chat ID: {$chatId}");
        }

        return response()->json(['ok' => true]);
    }
}
