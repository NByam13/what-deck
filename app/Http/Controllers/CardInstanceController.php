<?php

namespace App\Http\Controllers;

use App\Models\CardInstance;
use App\Models\Deck;
use App\Http\Requests\StoreCardInstanceRequest;
use App\Http\Requests\UpdateCardInstanceRequest;
use App\Http\Resources\CardInstanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Card Instances', description: 'Individual physical card instance management')]
class CardInstanceController extends Controller
{
    #[OA\Get(
        path: '/api/card-instances',
        description: 'Get a paginated list of card instances with optional filtering',
        summary: 'List all card instances',
        tags: ['Card Instances'],
        parameters: [
            new OA\Parameter(name: 'collection_id', description: 'Filter by collection ID', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'deck_id', description: 'Filter by deck ID', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'available', description: 'Filter available instances (not in deck)', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', example: true)),
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response',
                content: new OA\JsonContent(allOf: [
                    new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                    new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CardInstance'))])
                ]))
        ]
    )]
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

    #[OA\Post(
        path: '/api/card-instances',
        summary: 'Create a new card instance',
        description: 'Create a new physical card instance in a collection',
        tags: ['Card Instances'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['card_id', 'collection_id'],
            properties: [
                new OA\Property(property: 'card_id', type: 'integer', example: 1),
                new OA\Property(property: 'collection_id', type: 'integer', example: 1),
                new OA\Property(property: 'condition', type: 'string', enum: ['mint', 'near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'], example: 'near_mint'),
                new OA\Property(property: 'foil', type: 'boolean', example: false),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Card instance created', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function store(StoreCardInstanceRequest $request): JsonResponse
    {
        $cardInstance = CardInstance::create($request->validated());
        $cardInstance->load(['card', 'collection']);

        return response()->json(
            new CardInstanceResource($cardInstance),
            201
        );
    }

    #[OA\Get(
        path: '/api/card-instances/{id}',
        summary: 'Get a specific card instance',
        description: 'Retrieve a specific card instance with its relationships',
        tags: ['Card Instances'],
        parameters: [new OA\Parameter(name: 'id', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Successful response', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 404, description: 'Card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function show(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }

    #[OA\Put(
        path: '/api/card-instances/{id}',
        summary: 'Update a card instance',
        description: 'Update an existing card instance (condition, foil status)',
        tags: ['Card Instances'],
        parameters: [new OA\Parameter(name: 'id', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'condition', type: 'string', enum: ['mint', 'near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'], example: 'lightly_played'),
                new OA\Property(property: 'foil', type: 'boolean', example: true),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Card instance updated', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 404, description: 'Card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function update(UpdateCardInstanceRequest $request, CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->update($request->validated());
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }

    #[OA\Delete(
        path: '/api/card-instances/{id}',
        summary: 'Delete a card instance',
        description: 'Delete a physical card instance',
        tags: ['Card Instances'],
        parameters: [new OA\Parameter(name: 'id', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Card instance deleted', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 404, description: 'Card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function destroy(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->delete();

        return response()->json([
            'message' => 'Card instance deleted successfully'
        ]);
    }

    #[OA\Put(
        path: '/api/card-instances/{cardInstanceId}/move-to-deck/{deckId}',
        summary: 'Move card instance to deck',
        description: 'Move a card instance to a specific deck',
        tags: ['Card Instances'],
        parameters: [
            new OA\Parameter(name: 'cardInstanceId', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'deckId', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Card instance moved to deck', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 403, description: 'Deck and collection must belong to same user', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Card instance or deck not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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

    #[OA\Put(
        path: '/api/card-instances/{id}/remove-from-deck',
        summary: 'Remove card instance from deck',
        description: 'Remove a card instance from its current deck',
        tags: ['Card Instances'],
        parameters: [new OA\Parameter(name: 'id', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Card instance removed from deck', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 404, description: 'Card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function removeFromDeck(CardInstance $cardInstance): JsonResponse
    {
        $cardInstance->update(['deck_id' => null]);
        $cardInstance->load(['card', 'collection', 'deck']);

        return response()->json(new CardInstanceResource($cardInstance));
    }
}
