<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Http\Resources\CardResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Cards', description: 'Magic: The Gathering card management')]
class CardController extends Controller
{
    #[OA\Get(
        path: '/cards',
        description: 'Get a paginated list of Magic: The Gathering cards with optional filtering',
        summary: 'List all cards',
        tags: ['Cards'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                description: 'Filter cards by type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Instant')
            ),
            new OA\Parameter(
                name: 'subtype',
                description: 'Filter cards by subtype',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Spell')
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Search cards by title',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Lightning')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Card')
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
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

    #[OA\Post(
        path: '/cards',
        description: 'Create a new Magic: The Gathering card',
        summary: 'Create a new card',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'type'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Lightning Bolt'),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true, example: 'https://example.com/cards/lightning-bolt.jpg'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Lightning Bolt deals 3 damage to any target.'),
                    new OA\Property(property: 'cost', type: 'string', nullable: true, example: '{R}'),
                    new OA\Property(property: 'type', type: 'string', example: 'Instant'),
                    new OA\Property(property: 'subtype', type: 'string', nullable: true, example: 'Spell'),
                    new OA\Property(property: 'power', type: 'integer', nullable: true, minimum: 0, example: null),
                    new OA\Property(property: 'toughness', type: 'integer', nullable: true, minimum: 0, example: null),
                ]
            )
        ),
        tags: ['Cards'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Card created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Card')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function store(StoreCardRequest $request): JsonResponse
    {
        $card = Card::create($request->validated());

        return response()->json(
            new CardResource($card->load('cardInstances')),
            201
        );
    }

    #[OA\Get(
        path: '/cards/{id}',
        description: 'Retrieve a specific Magic: The Gathering card with its relationships',
        summary: 'Get a specific card',
        tags: ['Cards'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Card ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: '#/components/schemas/Card')
            ),
            new OA\Response(
                response: 404,
                description: 'Card not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function show(Card $card): JsonResponse
    {
        $card->load(['cardInstances.collection', 'cardInstances.deck']);

        return response()->json(new CardResource($card));
    }

    #[OA\Put(
        path: '/cards/{id}',
        summary: 'Update a card',
        description: 'Update an existing Magic: The Gathering card',
        tags: ['Cards'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Card ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Lightning Bolt'),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true, example: 'https://example.com/cards/lightning-bolt.jpg'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Lightning Bolt deals 3 damage to any target.'),
                    new OA\Property(property: 'cost', type: 'string', nullable: true, example: '{R}'),
                    new OA\Property(property: 'type', type: 'string', example: 'Instant'),
                    new OA\Property(property: 'subtype', type: 'string', nullable: true, example: 'Spell'),
                    new OA\Property(property: 'power', type: 'integer', nullable: true, minimum: 0, example: null),
                    new OA\Property(property: 'toughness', type: 'integer', nullable: true, minimum: 0, example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Card updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Card')
            ),
            new OA\Response(
                response: 404,
                description: 'Card not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function update(UpdateCardRequest $request, Card $card): JsonResponse
    {
        $card->update($request->validated());

        return response()->json(new CardResource($card->load('cardInstances')));
    }

    #[OA\Delete(
        path: '/cards/{id}',
        summary: 'Delete a card',
        description: 'Delete a Magic: The Gathering card (only if no instances exist)',
        tags: ['Cards'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Card ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Card deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Cannot delete card with existing instances',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Card not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
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
