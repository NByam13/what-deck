<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\CardInstance;
use App\Http\Requests\StoreDeckRequest;
use App\Http\Requests\UpdateDeckRequest;
use App\Http\Resources\DeckResource;
use App\Http\Resources\CardInstanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Deck::with('user')->withCount('cardInstances');

        // Filter by format if provided
        if ($request->has('format')) {
            $query->where('format', $request->format);
        }

        $decks = $query->paginate(15);

        return response()->json([
            'data' => DeckResource::collection($decks->items()),
            'meta' => [
                'current_page' => $decks->currentPage(),
                'last_page' => $decks->lastPage(),
                'per_page' => $decks->perPage(),
                'total' => $decks->total(),
            ],
            'links' => [
                'first' => $decks->url(1),
                'last' => $decks->url($decks->lastPage()),
                'prev' => $decks->previousPageUrl(),
                'next' => $decks->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeckRequest $request): JsonResponse
    {
        $deck = Deck::create($request->validated());

        return response()->json(
            new DeckResource($deck->load('user')),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Deck $deck): JsonResponse
    {
        $deck->load(['user', 'cardInstances.card']);
        
        return response()->json(new DeckResource($deck));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeckRequest $request, Deck $deck): JsonResponse
    {
        $deck->update($request->validated());

        return response()->json(new DeckResource($deck->load('user')));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deck $deck): JsonResponse
    {
        // Remove all card instances from the deck (set deck_id to null)
        $deck->cardInstances()->update(['deck_id' => null]);
        
        $deck->delete();

        return response()->json([
            'message' => 'Deck deleted successfully'
        ]);
    }

    /**
     * Get all card instances in a deck.
     */
    public function cardInstances(Deck $deck): JsonResponse
    {
        $cardInstances = $deck->cardInstances()
            ->with(['card', 'collection'])
            ->paginate(15);

        return response()->json([
            'data' => CardInstanceResource::collection($cardInstances->items()),
            'meta' => [
                'current_page' => $cardInstances->currentPage(),
                'last_page' => $cardInstances->lastPage(),
                'per_page' => $cardInstances->perPage(),
                'total' => $cardInstances->total(),
            ],
            'links' => [
                'first' => $cardInstances->url(1),
                'last' => $cardInstances->url($cardInstances->lastPage()),
                'prev' => $cardInstances->previousPageUrl(),
                'next' => $cardInstances->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Add a card instance to a deck.
     */
    public function addCardInstance(Deck $deck, CardInstance $cardInstance): JsonResponse
    {
        // Verify the deck and card instance belong to the same user
        if ($deck->user_id !== $cardInstance->collection->user_id) {
            return response()->json([
                'message' => 'Deck and card instance must belong to the same user',
                'errors' => [
                    'authorization' => ['You can only add your own cards to your own decks']
                ]
            ], 403);
        }

        // Check if card instance is already in a deck
        if ($cardInstance->isInDeck()) {
            return response()->json([
                'message' => 'Card instance is already assigned to a deck',
                'errors' => [
                    'card_instance' => ['This card instance is already assigned to a deck']
                ]
            ], 400);
        }

        $cardInstance->update(['deck_id' => $deck->id]);
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }

    /**
     * Remove a card instance from a deck.
     */
    public function removeCardInstance(Deck $deck, CardInstance $cardInstance): JsonResponse
    {
        // Verify the card instance belongs to this deck
        if ($cardInstance->deck_id !== $deck->id) {
            return response()->json([
                'message' => 'Card instance does not belong to this deck',
                'errors' => [
                    'card_instance' => ['This card instance does not belong to this deck']
                ]
            ], 400);
        }

        $cardInstance->update(['deck_id' => null]);
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }
}
