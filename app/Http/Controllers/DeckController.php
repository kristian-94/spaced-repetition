<?php

namespace App\Http\Controllers;

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
        $userId = Auth::id();

        // New cards introduced per day for last 7 days (across all decks)
        $newPerDay = ReviewLog::where('user_id', $userId)
            ->where('state_before', 0)
            ->where('reviewed_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(reviewed_at) as date, COUNT(DISTINCT card_id) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Fill in zeros for missing days
        $newPerDayFilled = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $newPerDayFilled[$date] = $newPerDay[$date] ?? 0;
        }

        $decks = Auth::user()->decks()
            ->withCount('cards')
            ->withCount(['cards as active_count' => fn($q) => $q->where('fsrs_state', '>', 0)])
            ->orderBy('name')
            ->get()
            ->map(function ($deck) {
                $deck->due_count = $this->fsrsService->getDueCount($deck);
                return $deck;
            });

        return Inertia::render('Decks/Index', [
            'decks'      => $decks,
            'totalDue'   => $decks->sum('due_count'),
            'newPerDay'  => $newPerDayFilled,
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
