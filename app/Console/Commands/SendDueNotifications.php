<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FsrsService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendDueNotifications extends Command
{
    protected $signature = 'notifications:send-due';
    protected $description = 'Send Telegram notifications for cards due today';

    public function handle(FsrsService $fsrsService, TelegramService $telegramService): void
    {
        $users = User::whereNotNull('telegram_chat_id')->get();

        foreach ($users as $user) {
            $activeDecks = $user->decks()->active()->get();

            $totalDue = 0;
            $decksDue = 0;

            foreach ($activeDecks as $deck) {
                $due = $fsrsService->getDueCount($deck);
                if ($due > 0) {
                    $totalDue += $due;
                    $decksDue++;
                }
            }

            if ($totalDue > 0) {
                $deckWord = $decksDue === 1 ? 'deck' : 'decks';
                $telegramService->send(
                    $user,
                    "📚 You have <b>{$totalDue}</b> cards due across <b>{$decksDue}</b> {$deckWord}. Time to review!"
                );

                $this->info("Notified user {$user->id} ({$user->email}): {$totalDue} cards due");
            }
        }

        $this->info('Done.');
    }
}
