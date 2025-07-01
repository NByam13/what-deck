<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User Resource',
    description: 'User resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(
            property: 'email_verified_at',
            type: 'string',
            format: 'date-time',
            example: '2024-01-01T12:00:00.000000Z',
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(
            property: 'collections',
            description: 'User collections (only when relationship is loaded)',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Collection')
        ),
        new OA\Property(
            property: 'decks',
            description: 'User decks (only when relationship is loaded)',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Deck')
        ),
        new OA\Property(
            property: 'total_collections',
            description: 'Total number of collections owned by user',
            type: 'integer',
            example: 3
        ),
        new OA\Property(
            property: 'total_decks',
            description: 'Total number of decks owned by user',
            type: 'integer',
            example: 5
        ),
    ],
    type: 'object'
)]
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships - only include if loaded
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'decks' => DeckResource::collection($this->whenLoaded('decks')),

            // Computed attributes
            'total_collections' => $this->when(
                $this->relationLoaded('collections'),
                fn() => $this->collections->count()
            ),
            'total_decks' => $this->when(
                $this->relationLoaded('decks'),
                fn() => $this->decks->count()
            ),
        ];
    }
}
