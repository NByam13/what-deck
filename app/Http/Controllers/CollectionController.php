<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CardInstanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = Collection::create($request->validated());

        return response()->json(
            new CollectionResource($collection->load('user')),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Collection $collection): JsonResponse
    {
        $collection->load(['user', 'cardInstances.card', 'cardInstances.deck']);
        
        return response()->json(new CollectionResource($collection));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $collection->update($request->validated());

        return response()->json(new CollectionResource($collection->load('user')));
    }

    /**
     * Remove the specified resource from storage.
     */
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

    /**
     * Get all card instances in a collection.
     */
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
