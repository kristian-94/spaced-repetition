<?php

namespace App\Http\Controllers;

use App\Models\Deck;
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
        $decks = Auth::user()->decks()
            ->withCount('cards')
            ->orderBy('name')
            ->get()
            ->map(function ($deck) {
                $deck->due_count = $this->fsrsService->getDueCount($deck);
                return $deck;
            });

        return Inertia::render('Decks/Index', [
            'decks'    => $decks,
            'totalDue' => $decks->sum('due_count'),
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
