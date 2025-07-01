<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Collection',
    title: 'Collection Resource',
    description: 'Card collection resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'My Main Collection'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Collection of rare and valuable cards'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(
            property: 'user',
            ref: '#/components/schemas/User',
            description: 'Collection owner (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'card_instances',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CardInstance'),
            description: 'Card instances in this collection (only when relationship is loaded)'
        ),
        new OA\Property(property: 'total_card_instances', type: 'integer', description: 'Total number of card instances in collection', example: 150),
        new OA\Property(property: 'cards_in_decks', type: 'integer', description: 'Number of cards currently assigned to decks', example: 60),
        new OA\Property(property: 'available_cards', type: 'integer', description: 'Number of cards not assigned to decks', example: 90),
        new OA\Property(property: 'unique_cards', type: 'integer', description: 'Number of unique card types in collection', example: 75),
    ],
    type: 'object'
)]
class CollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships - only include if loaded
            'user' => new UserResource($this->whenLoaded('user')),
            'card_instances' => CardInstanceResource::collection($this->whenLoaded('cardInstances')),

            // Computed attributes
            'total_card_instances' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->count()
            ),
            'cards_in_decks' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->whereNotNull('deck_id')->count()
            ),
            'available_cards' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->whereNull('deck_id')->count()
            ),
            'unique_cards' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->unique('card_id')->count()
            ),
        ];
    }
}
