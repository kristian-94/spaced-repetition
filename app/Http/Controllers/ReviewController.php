<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Services\FsrsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function __construct(private FsrsService $fsrsService)
    {
    }

    public function index(Deck $deck)
    {
        $this->authorize('view', $deck);

        $dueCount = $this->fsrsService->getDueCount($deck);
        $card = $this->fsrsService->getNextCard($deck);

        if ($card) {
            $card->front_image_url = $card->front_image
                ? Storage::url($card->front_image)
                : null;
            $card->back_image_url = $card->back_image
                ? Storage::url($card->back_image)
                : null;
        }

        // Find next due time for the "all done" message
        $nextDue = null;
        if ($dueCount === 0) {
            $nextCard = $deck->cards()
                ->where('is_suspended', false)
                ->where('fsrs_due', '>', now())
                ->orderBy('fsrs_due', 'asc')
                ->first();
            $nextDue = $nextCard ? $nextCard->fsrs_due : null;
        }

        return Inertia::render('Review/Index', [
            'deck' => $deck,
            'card' => $card,
            'dueCount' => $dueCount,
            'nextDue' => $nextDue,
        ]);
    }

    public function submit(Request $request, Deck $deck)
    {
        $this->authorize('view', $deck);

        $validated = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'rating' => 'required|integer|in:1,2,3,4',
            'duration_ms' => 'nullable|integer|min:0',
        ]);

        $card = $deck->cards()->findOrFail($validated['card_id']);

        $this->fsrsService->review(
            $card,
            $validated['rating'],
            $validated['duration_ms'] ?? null
        );

        return redirect()->route('review.index', $deck);
    }
}
