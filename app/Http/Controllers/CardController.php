<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Http\Resources\CardResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Card::query();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by subtype if provided
        if ($request->has('subtype')) {
            $query->where('subtype', $request->subtype);
        }

        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $cards = $query->with('cardInstances')->paginate(15);

        return response()->json([
            'data' => CardResource::collection($cards->items()),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
            ],
            'links' => [
                'first' => $cards->url(1),
                'last' => $cards->url($cards->lastPage()),
                'prev' => $cards->previousPageUrl(),
                'next' => $cards->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCardRequest $request): JsonResponse
    {
        $card = Card::create($request->validated());

        return response()->json(
            new CardResource($card->load('cardInstances')),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card): JsonResponse
    {
        $card->load(['cardInstances.collection', 'cardInstances.deck']);
        
        return response()->json(new CardResource($card));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCardRequest $request, Card $card): JsonResponse
    {
        $card->update($request->validated());

        return response()->json(new CardResource($card->load('cardInstances')));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card): JsonResponse
    {
        // Check if there are any card instances
        if ($card->cardInstances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete card with existing instances',
                'errors' => [
                    'card' => ['This card has existing instances and cannot be deleted']
                ]
            ], 400);
        }

        $card->delete();

        return response()->json([
            'message' => 'Card deleted successfully'
        ]);
    }
}
