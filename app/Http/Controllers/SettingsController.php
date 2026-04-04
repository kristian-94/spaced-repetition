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
            'telegram_chat_id' => $user->telegram_chat_id,
            'tokens' => $tokens,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string|max:100',
        ]);

        Auth::user()->update($validated);

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

        if (!$user->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'Please set your Telegram Chat ID first.']);
        }

        app(TelegramService::class)->send($user, '✅ Test notification from your Spaced Repetition app!');

        return back()->with('success', 'Test notification sent!');
    }
}
