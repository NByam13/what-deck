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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Decks', description: 'Magic: The Gathering deck management')]
class DeckController extends Controller
{
    #[OA\Get(
        path: '/decks',
        description: 'Get a paginated list of decks with optional filtering',
        summary: 'List all decks',
        tags: ['Decks'],
        parameters: [
            new OA\Parameter(name: 'format', description: 'Filter by deck format', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['Standard', 'Modern', 'Legacy', 'Vintage', 'Commander', 'Pioneer', 'Historic', 'Pauper', 'Limited'], example: 'Modern')),
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response',
                content: new OA\JsonContent(allOf: [
                    new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                    new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Deck'))])
                ]))
        ]
    )]
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

    #[OA\Post(
        path: '/decks',
        summary: 'Create a new deck',
        description: 'Create a new Magic: The Gathering deck',
        tags: ['Decks'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['user_id', 'name'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Lightning Burn Deck'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Fast aggressive red deck'),
                new OA\Property(property: 'format', type: 'string', nullable: true, enum: ['Standard', 'Modern', 'Legacy', 'Vintage', 'Commander', 'Pioneer', 'Historic', 'Pauper', 'Limited'], example: 'Modern'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Deck created', content: new OA\JsonContent(ref: '#/components/schemas/Deck')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function store(StoreDeckRequest $request): JsonResponse
    {
        $deck = Deck::create($request->validated());

        return response()->json(
            new DeckResource($deck->load('user')),
            201
        );
    }

    #[OA\Get(
        path: '/decks/{id}',
        summary: 'Get a specific deck',
        description: 'Retrieve a specific deck with its card instances',
        tags: ['Decks'],
        parameters: [new OA\Parameter(name: 'id', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Successful response', content: new OA\JsonContent(ref: '#/components/schemas/Deck')),
            new OA\Response(response: 404, description: 'Deck not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function show(Deck $deck): JsonResponse
    {
        $deck->load(['user', 'cardInstances.card']);

        return response()->json(new DeckResource($deck));
    }

    #[OA\Put(
        path: '/decks/{id}',
        summary: 'Update a deck',
        description: 'Update an existing deck',
        tags: ['Decks'],
        parameters: [new OA\Parameter(name: 'id', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Deck Name'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                new OA\Property(property: 'format', type: 'string', nullable: true, enum: ['Standard', 'Modern', 'Legacy', 'Vintage', 'Commander', 'Pioneer', 'Historic', 'Pauper', 'Limited'], example: 'Standard'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Deck updated', content: new OA\JsonContent(ref: '#/components/schemas/Deck')),
            new OA\Response(response: 404, description: 'Deck not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function update(UpdateDeckRequest $request, Deck $deck): JsonResponse
    {
        $deck->update($request->validated());

        return response()->json(new DeckResource($deck->load('user')));
    }

    #[OA\Delete(
        path: '/decks/{id}',
        summary: 'Delete a deck',
        description: 'Delete a deck and remove all card instances from it',
        tags: ['Decks'],
        parameters: [new OA\Parameter(name: 'id', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Deck deleted', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 404, description: 'Deck not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function destroy(Deck $deck): JsonResponse
    {
        // Remove all card instances from the deck (set deck_id to null)
        $deck->cardInstances()->update(['deck_id' => null]);

        $deck->delete();

        return response()->json([
            'message' => 'Deck deleted successfully'
        ]);
    }

    #[OA\Get(
        path: '/decks/{id}/card-instances',
        summary: 'Get deck card instances',
        description: 'Get all card instances in a specific deck',
        tags: ['Decks'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response',
                content: new OA\JsonContent(allOf: [
                    new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                    new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CardInstance'))])
                ])),
            new OA\Response(response: 404, description: 'Deck not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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

    #[OA\Post(
        path: '/decks/{deckId}/add-card-instance/{cardInstanceId}',
        summary: 'Add card instance to deck',
        description: 'Add a specific card instance to a deck',
        tags: ['Decks'],
        parameters: [
            new OA\Parameter(name: 'deckId', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'cardInstanceId', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Card instance added to deck', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 400, description: 'Card instance already assigned to a deck', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Deck and card instance must belong to same user', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Deck or card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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

    #[OA\Delete(
        path: '/decks/{deckId}/remove-card-instance/{cardInstanceId}',
        summary: 'Remove card instance from deck',
        description: 'Remove a specific card instance from a deck',
        tags: ['Decks'],
        parameters: [
            new OA\Parameter(name: 'deckId', description: 'Deck ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'cardInstanceId', description: 'Card Instance ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Card instance removed from deck', content: new OA\JsonContent(ref: '#/components/schemas/CardInstance')),
            new OA\Response(response: 400, description: 'Card instance does not belong to this deck', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Deck or card instance not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
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
