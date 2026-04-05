<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    // ---- Decks ----

    public function listDecks(Request $request)
    {
        $decks = Auth::user()->decks()
            ->withCount('cards')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $decks]);
    }

    public function createDeck(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $deck = Auth::user()->decks()->create($validated);

        return response()->json(['data' => $deck], 201);
    }

    // ---- Cards ----

    public function listCards(Request $request, Deck $deck)
    {
        $this->authorizeDeck($deck);

        $cards = $deck->cards()
            ->orderBy('fsrs_due', 'asc')
            ->paginate(100);

        return response()->json($cards);
    }

    public function createCards(Request $request, Deck $deck)
    {
        $this->authorizeDeck($deck);

        $payload = $request->all();

        // Accept {cards: [...]} wrapper, single card object, or bare array of cards
        if (isset($payload['cards']) && is_array($payload['cards'])) {
            $payload = $payload['cards'];
        } elseif (isset($payload['front']) || isset($payload['front_content'])) {
            $payload = [$payload];
        }

        $created = [];
        foreach ($payload as $cardData) {
            $validated = validator($cardData, [
                'front' => 'sometimes|required_without:front_content|string',
                'front_content' => 'sometimes|required_without:front|string',
                'back' => 'sometimes|required_without:back_content|string',
                'back_content' => 'sometimes|required_without:back|string',
                'front_image_base64' => 'nullable|string',
                'back_image_base64' => 'nullable|string',
                'front_image_url' => 'nullable|string|url',
                'back_image_url' => 'nullable|string|url',
                'card_type' => 'nullable|string|in:basic',
            ])->validate();

            $frontContent = $validated['front_content'] ?? $validated['front'] ?? null;
            $backContent = $validated['back_content'] ?? $validated['back'] ?? null;

            $cardFields = [
                'user_id' => Auth::id(),
                'front_content' => $frontContent,
                'back_content' => $backContent,
                'card_type' => $validated['card_type'] ?? 'basic',
                'fsrs_due' => now(),
            ];

            // Handle base64 images
            if (!empty($validated['front_image_base64'])) {
                $cardFields['front_image'] = $this->storeBase64Image($validated['front_image_base64']);
            }
            if (!empty($validated['back_image_base64'])) {
                $cardFields['back_image'] = $this->storeBase64Image($validated['back_image_base64']);
            }

            // Handle external image URLs
            if (!empty($validated['front_image_url'])) {
                $cardFields['front_image_url'] = $validated['front_image_url'];
            }
            if (!empty($validated['back_image_url'])) {
                $cardFields['back_image_url'] = $validated['back_image_url'];
            }

            $query = $deck->cards()->where('front_content', $frontContent);
            // If the card has a front image URL, include it in the match so
            // multiple image cards with the same question text are distinct.
            if (!empty($cardFields['front_image_url'])) {
                $query->where('front_image_url', $cardFields['front_image_url']);
            } else {
                $query->whereNull('front_image_url');
            }
            $existing = $query->first();

            if ($existing) {
                unset($cardFields['user_id'], $cardFields['fsrs_due']);
                $existing->update($cardFields);
                $created[] = $existing;
            } else {
                $created[] = $deck->cards()->create($cardFields);
            }
        }

        return response()->json(['data' => $created, 'count' => count($created)], 201);
    }

    public function updateCard(Request $request, Card $card)
    {
        $this->authorizeCard($card);

        $validated = $request->validate([
            'front_content' => 'sometimes|required|string',
            'back_content' => 'sometimes|required|string',
            'front_image_base64' => 'nullable|string',
            'back_image_base64' => 'nullable|string',
        ]);

        if (!empty($validated['front_image_base64'])) {
            if ($card->front_image) {
                Storage::disk('public')->delete($card->front_image);
            }
            $validated['front_image'] = $this->storeBase64Image($validated['front_image_base64']);
        }
        if (!empty($validated['back_image_base64'])) {
            if ($card->back_image) {
                Storage::disk('public')->delete($card->back_image);
            }
            $validated['back_image'] = $this->storeBase64Image($validated['back_image_base64']);
        }

        unset($validated['front_image_base64'], $validated['back_image_base64']);
        $card->update($validated);

        return response()->json(['data' => $card]);
    }

    public function deleteCard(Request $request, Card $card)
    {
        $this->authorizeCard($card);

        if ($card->front_image) {
            Storage::disk('public')->delete($card->front_image);
        }
        if ($card->back_image) {
            Storage::disk('public')->delete($card->back_image);
        }

        $card->delete();

        return response()->json(['message' => 'Card deleted.']);
    }

    public function suspendCard(Request $request, Card $card)
    {
        $this->authorizeCard($card);
        $card->update(['is_suspended' => !$card->is_suspended]);

        return response()->json(['data' => $card]);
    }

    // ---- Helpers ----

    private function authorizeDeck(Deck $deck): void
    {
        if ($deck->user_id !== Auth::id()) {
            abort(403, 'Forbidden');
        }
    }

    private function authorizeCard(Card $card): void
    {
        if ($card->user_id !== Auth::id()) {
            abort(403, 'Forbidden');
        }
    }

    private function storeBase64Image(string $base64): string
    {
        // Strip data URI prefix if present
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $data = base64_decode($base64);
        $filename = 'cards/' . uniqid() . '.jpg';
        Storage::disk('public')->put($filename, $data);

        return $filename;
    }
}
