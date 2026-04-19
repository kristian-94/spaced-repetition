<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $tokens = $user->tokens()->select(['id', 'name', 'last_used_at', 'created_at'])->get();

        return Inertia::render('Settings/Index', [
            'telegram_chat_id' => $user->isAdmin() ? $user->telegram_chat_id : null,
            'daily_new_cards_limit' => $user->daily_new_cards_limit ?? 20,
            'tokens' => $tokens,
            'baseUrl' => rtrim(config('app.url'), '/'),
            'can_use_telegram' => $user->isAdmin(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'daily_new_cards_limit' => 'sometimes|integer|min:1|max:9999',
        ];

        if ($user->isAdmin()) {
            $rules['telegram_chat_id'] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        return back()->with('success', 'Settings saved.');
    }

    public function generateToken(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $token = Auth::user()->createToken($validated['name'], ['api']);

        return back()->with([
            'success' => 'Token created.',
            'new_token' => $token->plainTextToken,
        ]);
    }

    public function revokeToken(Request $request, int $tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'Token revoked.');
    }

    public function testNotification(Request $request)
    {
        $user = Auth::user();

        abort_unless($user->isAdmin(), 403);

        if (!$user->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'Please set your Telegram Chat ID first.']);
        }

        app(TelegramService::class)->send($user, '✅ Test notification from your Spaced Repetition app!');

        return back()->with('success', 'Test notification sent!');
    }
}
