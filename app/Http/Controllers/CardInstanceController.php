<?php

namespace App\Http\Controllers;

use App\Models\CardInstance;
use App\Models\Deck;
use App\Http\Requests\StoreCardInstanceRequest;
use App\Http\Requests\UpdateCardInstanceRequest;
use App\Http\Resources\CardInstanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CardInstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CardInstance::with(['card', 'collection', 'deck']);

        // Filter by collection if provided
        if ($request->has('collection_id')) {
            $query->where('collection_id', $request->collection_id);
        }

        // Filter by deck if provided
        if ($request->has('deck_id')) {
            $query->where('deck_id', $request->deck_id);
        }

        // Filter by availability (not in deck)
        if ($request->has('available') && $request->available) {
            $query->available();
        }

        $cardInstances = $query->paginate(15);

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
     * Store a newly created resource in storage.
     */
    public function store(StoreCardInstanceRequest $request): JsonResponse
    {
        $cardInstance = CardInstance::create($request->validated());
        $cardInstance->load(['card', 'collection']);

        return response()->json(
            new CardInstanceResource($cardInstance),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->load(['card', 'collection', 'deck']);
        
        return response()->json(new CardInstanceResource($cardInstance));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCardInstanceRequest $request, CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->update($request->validated());
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->delete();

        return response()->json([
            'message' => 'Card instance deleted successfully'
        ]);
    }

    /**
     * Move a card instance to a deck.
     */
    public function moveToDeck(CardInstance $cardInstance, Deck $deck): JsonResponse
    {
        // Verify the deck belongs to the same user as the collection
        if ($cardInstance->collection->user_id !== $deck->user_id) {
            return response()->json([
                'message' => 'Deck and collection must belong to the same user',
                'errors' => [
                    'authorization' => ['You can only move cards between your own collections and decks']
                ]
            ], 403);
        }

        $cardInstance->update(['deck_id' => $deck->id]);
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }

    /**
     * Remove a card instance from its deck.
     */
    public function removeFromDeck(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->update(['deck_id' => null]);
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }
}
