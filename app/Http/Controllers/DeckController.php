<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckDailyBoost;
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

        $todayDate = now($sydney)->toDateString();
        $boostTotal = DeckDailyBoost::whereIn('deck_id', $user->decks()->active()->pluck('id'))
            ->where('date', $todayDate)
            ->sum('extra_cards');

        $effectiveLimit = $limit + $boostTotal;

        $totalNewAvailable = Card::where('user_id', $userId)
            ->where('fsrs_state', 0)
            ->where('is_suspended', false)
            ->count();

        $newRemainingToday = min(max(0, $effectiveLimit - $newSeenToday), $totalNewAvailable);
        $newCardsToday     = $newSeenToday + $newRemainingToday;

        // Cumulative mastered cards per deck over last 30 days
        // A card counts as "mastered" the first time it reaches review state (state=2)
        $masteredLogs = ReviewLog::where('user_id', $userId)
            ->where('state_after', 2)
            ->where('state_before', '!=', 2)
            ->where('reviewed_at', '>=', now($sydney)->subDays(29)->startOfDay()->utc())
            ->select('deck_id', 'card_id', 'reviewed_at')
            ->get()
            ->groupBy('deck_id')
            ->map(function ($logs) use ($sydney) {
                // Only count each card once (first time it hit review)
                return $logs->sortBy('reviewed_at')
                    ->unique('card_id')
                    ->groupBy(fn($log) => $log->reviewed_at->setTimezone($sydney)->format('Y-m-d'))
                    ->map->count();
            });

        // Count mastered cards before the 30-day window per deck
        $masteredBeforeWindow = ReviewLog::where('user_id', $userId)
            ->where('state_after', 2)
            ->where('state_before', '!=', 2)
            ->where('reviewed_at', '<', now($sydney)->subDays(29)->startOfDay()->utc())
            ->selectRaw('deck_id, COUNT(DISTINCT card_id) as count')
            ->groupBy('deck_id')
            ->pluck('count', 'deck_id');

        $decks = $user->decks()
            ->withCount('cards')
            ->withCount(['cards as active_count' => fn($q) => $q->where('fsrs_state', '>', 0)])
            ->withCount(['cards as learning_count' => fn($q) => $q->whereIn('fsrs_state', [1, 3])])
            ->withCount(['cards as mastered_count' => fn($q) => $q->where('fsrs_state', 2)->where('fsrs_difficulty', '<=', 5)])
            ->withCount(['cards as difficult_count' => fn($q) => $q->where('fsrs_state', 2)->where('fsrs_difficulty', '>', 5)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($deck) use ($newTodayByDeck, $masteredLogs, $masteredBeforeWindow, $sydney) {
                ['review' => $reviewDue, 'new' => $newDue] = $this->fsrsService->getDueCounts($deck);
                $deck->due_count      = $reviewDue + $newDue;
                $deck->review_due     = $reviewDue;
                $deck->new_due        = $newDue;
                $deck->new_today      = $newTodayByDeck[$deck->id] ?? 0;

                // Build cumulative mastered trend (30 days)
                $dailyCounts = $masteredLogs[$deck->id] ?? collect();
                $cumulative = $masteredBeforeWindow[$deck->id] ?? 0;
                $trend = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now($sydney)->subDays($i)->format('Y-m-d');
                    $cumulative += $dailyCounts[$date] ?? 0;
                    $trend[] = $cumulative;
                }
                $deck->mastered_trend = $trend;

                return $deck;
            });

        return Inertia::render('Decks/Index', [
            'decks'         => $decks,
            'totalDue'      => $decks->sum('due_count'),
            'newPerDay'     => $newPerDayFilled,
            'newCardsToday' => $newCardsToday,
            'dailyLimit'    => $effectiveLimit,
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

        return redirect()->route('cards.index', $deck)
            ->with('success', "Deck \"{$deck->name}\" created. Add cards manually, or use the API from an AI agent.");
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

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:decks,id',
        ]);

        $user = Auth::user();

        foreach ($validated['order'] as $position => $deckId) {
            $user->decks()->where('id', $deckId)->update(['sort_order' => $position]);
        }

        return response()->noContent();
    }
}
