<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CardInstanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Collections', description: 'Card collection management')]
class CollectionController extends Controller
{
    #[OA\Get(
        path: '/api/collections',
        description: 'Get a paginated list of card collections',
        summary: 'List all collections',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Collection'))])
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // For now, return all collections. In production, filter by authenticated user
        $collections = Collection::with('user')->withCount('cardInstances')->paginate(15);

        return response()->json([
            'data' => CollectionResource::collection($collections->items()),
            'meta' => [
                'current_page' => $collections->currentPage(),
                'last_page' => $collections->lastPage(),
                'per_page' => $collections->perPage(),
                'total' => $collections->total(),
            ],
            'links' => [
                'first' => $collections->url(1),
                'last' => $collections->url($collections->lastPage()),
                'prev' => $collections->previousPageUrl(),
                'next' => $collections->nextPageUrl(),
            ]
        ]);
    }

    #[OA\Post(
        path: '/api/collections',
        summary: 'Create a new collection',
        description: 'Create a new card collection',
        tags: ['Collections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'name'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'My Main Collection'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Collection of rare and valuable cards'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Collection created', content: new OA\JsonContent(ref: '#/components/schemas/Collection')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = Collection::create($request->validated());

        return response()->json(
            new CollectionResource($collection->load('user')),
            201
        );
    }

    #[OA\Get(
        path: '/api/collections/{id}',
        summary: 'Get a specific collection',
        description: 'Retrieve a specific collection with its relationships',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Collection ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response', content: new OA\JsonContent(ref: '#/components/schemas/Collection')),
            new OA\Response(response: 404, description: 'Collection not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function show(Collection $collection): JsonResponse
    {
        $collection->load(['user', 'cardInstances.card', 'cardInstances.deck']);

        return response()->json(new CollectionResource($collection));
    }

    #[OA\Put(
        path: '/api/collections/{id}',
        summary: 'Update a collection',
        description: 'Update an existing collection',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Collection ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Updated Collection Name'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Collection updated', content: new OA\JsonContent(ref: '#/components/schemas/Collection')),
            new OA\Response(response: 404, description: 'Collection not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]
    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $collection->update($request->validated());

        return response()->json(new CollectionResource($collection->load('user')));
    }

    #[OA\Delete(
        path: '/api/collections/{id}',
        summary: 'Delete a collection',
        description: 'Delete a collection (only if no card instances exist)',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Collection ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Collection deleted', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: 'Cannot delete collection with card instances', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Collection not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function destroy(Collection $collection): JsonResponse
    {
        // Check if there are any card instances
        if ($collection->cardInstances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete collection with card instances',
                'errors' => [
                    'collection' => ['This collection contains card instances and cannot be deleted']
                ]
            ], 400);
        }

        $collection->delete();

        return response()->json([
            'message' => 'Collection deleted successfully'
        ]);
    }

    #[OA\Get(
        path: '/api/collections/{id}/card-instances',
        summary: 'Get collection card instances',
        description: 'Get all card instances in a specific collection',
        tags: ['Collections'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Collection ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/PaginatedResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CardInstance'))])
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Collection not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function cardInstances(Collection $collection): JsonResponse
    {
        $cardInstances = $collection->cardInstances()
            ->with(['card', 'deck'])
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
}
