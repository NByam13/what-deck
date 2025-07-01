<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Card',
    title: 'Card Resource',
    description: 'Magic: The Gathering card resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Lightning Bolt'),
        new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true, example: 'https://example.com/cards/lightning-bolt.jpg'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Lightning Bolt deals 3 damage to any target.'),
        new OA\Property(property: 'cost', type: 'string', nullable: true, example: '{R}'),
        new OA\Property(property: 'type', type: 'string', example: 'Instant'),
        new OA\Property(property: 'subtype', type: 'string', nullable: true, example: 'Spell'),
        new OA\Property(property: 'power', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'toughness', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(
            property: 'card_instances',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CardInstance'),
            description: 'Card instances (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'collections',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Collection'),
            description: 'Collections containing this card (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'decks',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Deck'),
            description: 'Decks containing this card (only when relationship is loaded)'
        ),
        new OA\Property(property: 'total_instances', type: 'integer', description: 'Total number of instances of this card', example: 5),
        new OA\Property(property: 'instances_in_decks', type: 'integer', description: 'Number of instances currently in decks', example: 2),
        new OA\Property(property: 'available_instances', type: 'integer', description: 'Number of instances not in decks', example: 3),
    ],
    type: 'object'
)]
class CardResource extends JsonResource
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
            'title' => $this->title,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'cost' => $this->cost,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'power' => $this->power,
            'toughness' => $this->toughness,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships - only include if loaded
            'card_instances' => CardInstanceResource::collection($this->whenLoaded('cardInstances')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'decks' => DeckResource::collection($this->whenLoaded('decks')),

            // Computed attributes
            'total_instances' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->count()
            ),
            'instances_in_decks' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->whereNotNull('deck_id')->count()
            ),
            'available_instances' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->whereNull('deck_id')->count()
            ),
        ];
    }
}
