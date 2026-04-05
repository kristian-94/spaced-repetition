<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Models\ReviewLog;
use App\Services\FsrsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DeckController extends Controller
{
    public function __construct(private FsrsService $fsrsService)
    {
    }

    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;

        $sydney = 'Australia/Sydney';
        $todayStart = now($sydney)->startOfDay()->utc();
        $todayEnd   = now($sydney)->endOfDay()->utc();

        // New cards introduced per day for last 7 days — group by Sydney date in PHP
        // (avoids CONVERT_TZ which is MySQL-only)
        $recentLogs = ReviewLog::where('user_id', $userId)
            ->where('state_before', 0)
            ->where('reviewed_at', '>=', now($sydney)->subDays(6)->startOfDay()->utc())
            ->select('card_id', 'reviewed_at')
            ->get();

        $newPerDay = $recentLogs
            ->groupBy(fn($log) => $log->reviewed_at->setTimezone($sydney)->format('Y-m-d'))
            ->map(fn($group) => $group->unique('card_id')->count());

        // Fill in zeros for missing days
        $newPerDayFilled = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now($sydney)->subDays($i)->format('Y-m-d');
            $newPerDayFilled[$date] = $newPerDay[$date] ?? 0;
        }

        // New cards introduced today, total and per deck
        $limit = $user->daily_new_cards_limit ?: 20;

        $newTodayByDeck = ReviewLog::where('user_id', $userId)
            ->whereBetween('reviewed_at', [$todayStart, $todayEnd])
            ->where('state_before', 0)
            ->selectRaw('deck_id, COUNT(DISTINCT card_id) as count')
            ->groupBy('deck_id')
            ->pluck('count', 'deck_id');

        $newSeenToday = $newTodayByDeck->sum();

        $totalNewAvailable = Card::where('user_id', $userId)
            ->where('fsrs_state', 0)
            ->where('is_suspended', false)
            ->count();

        $newRemainingToday = min(max(0, $limit - $newSeenToday), $totalNewAvailable);
        $newCardsToday     = $newSeenToday + $newRemainingToday;

        $decks = $user->decks()
            ->withCount('cards')
            ->withCount(['cards as active_count' => fn($q) => $q->where('fsrs_state', '>', 0)])
            ->orderBy('name')
            ->get()
            ->map(function ($deck) use ($newTodayByDeck) {
                $deck->due_count      = $this->fsrsService->getDueCount($deck);
                $deck->new_today      = $newTodayByDeck[$deck->id] ?? 0;
                return $deck;
            });

        return Inertia::render('Decks/Index', [
            'decks'         => $decks,
            'totalDue'      => $decks->sum('due_count'),
            'newPerDay'     => $newPerDayFilled,
            'newCardsToday' => $newCardsToday,
            'dailyLimit'    => $limit,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $deck = Auth::user()->decks()->create($validated);

        return redirect()->route('decks.index')
            ->with('success', "Deck \"{$deck->name}\" created.");
    }

    public function update(Request $request, Deck $deck)
    {
        $this->authorize('update', $deck);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:20',
            'new_cards_per_day' => 'integer|min:1|max:9999',
            'tts_language' => 'nullable|string|max:20',
        ]);

        $deck->update($validated);

        return back()->with('success', 'Deck updated.');
    }

    public function destroy(Deck $deck)
    {
        $this->authorize('delete', $deck);
        $name = $deck->name;
        $deck->delete();

        return redirect()->route('decks.index')
            ->with('success', "Deck \"{$name}\" deleted.");
    }

    public function toggleActive(Deck $deck)
    {
        $this->authorize('update', $deck);
        $deck->update(['is_active' => !$deck->is_active]);

        return back()->with('success', 'Deck updated.');
    }
}
