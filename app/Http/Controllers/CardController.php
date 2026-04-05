<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CardController extends Controller
{
    public function index(Deck $deck)
    {
        $this->authorize('view', $deck);

        $cards = $deck->cards()
            ->orderBy('fsrs_due', 'asc')
            ->paginate(25)
            ->through(function ($card) {
                $card->front_image_url = $card->front_image
                    ? Storage::url($card->front_image)
                    : $card->front_image_url;
                $card->back_image_url = $card->back_image
                    ? Storage::url($card->back_image)
                    : $card->back_image_url;
                return $card;
            });

        return Inertia::render('Decks/Show', [
            'deck' => $deck,
            'cards' => $cards,
        ]);
    }

    public function store(Request $request, Deck $deck)
    {
        $this->authorize('update', $deck);

        $validated = $request->validate([
            'front_content' => 'required|string',
            'back_content' => 'required|string',
            'front_image' => 'nullable|image|max:5120',
            'back_image' => 'nullable|image|max:5120',
            'card_type' => 'nullable|string|in:basic',
        ]);

        if ($request->hasFile('front_image')) {
            $validated['front_image'] = $request->file('front_image')
                ->store('cards', 'public');
        }

        if ($request->hasFile('back_image')) {
            $validated['back_image'] = $request->file('back_image')
                ->store('cards', 'public');
        }

        $card = $deck->cards()->create(array_merge($validated, [
            'user_id' => Auth::id(),
            'fsrs_due' => now(),
        ]));

        return back()->with('success', 'Card created.');
    }

    public function update(Request $request, Card $card)
    {
        $this->authorize('update', $card);

        $validated = $request->validate([
            'front_content' => 'required|string',
            'back_content' => 'required|string',
            'front_image' => 'nullable|image|max:5120',
            'back_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('front_image')) {
            if ($card->front_image) {
                Storage::disk('public')->delete($card->front_image);
            }
            $validated['front_image'] = $request->file('front_image')
                ->store('cards', 'public');
        }

        if ($request->hasFile('back_image')) {
            if ($card->back_image) {
                Storage::disk('public')->delete($card->back_image);
            }
            $validated['back_image'] = $request->file('back_image')
                ->store('cards', 'public');
        }

        $card->update($validated);

        return back()->with('success', 'Card updated.');
    }

    public function destroy(Card $card)
    {
        $this->authorize('delete', $card);

        if ($card->front_image) {
            Storage::disk('public')->delete($card->front_image);
        }
        if ($card->back_image) {
            Storage::disk('public')->delete($card->back_image);
        }

        $card->delete();

        return back()->with('success', 'Card deleted.');
    }

    public function suspend(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['is_suspended' => !$card->is_suspended]);

        return back()->with('success', 'Card updated.');
    }
}
