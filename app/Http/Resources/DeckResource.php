<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Deck',
    title: 'Deck Resource',
    description: 'Magic: The Gathering deck resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Lightning Burn Deck'),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Fast aggressive red deck focused on direct damage',
            nullable: true
        ),
        new OA\Property(
            property: 'format',
            type: 'string',
            enum: ['Standard', 'Modern', 'Legacy', 'Vintage', 'Commander', 'Pioneer', 'Historic', 'Pauper', 'Limited'],
            example: 'Modern',
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(
            property: 'user',
            ref: '#/components/schemas/User',
            description: 'Deck owner (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'card_instances',
            description: 'Card instances in this deck (only when relationship is loaded)',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CardInstance')
        ),
        new OA\Property(
            property: 'total_cards',
            description: 'Total number of cards in deck',
            type: 'integer',
            example: 60
        ),
        new OA\Property(
            property: 'unique_cards',
            description: 'Number of unique card types in deck',
            type: 'integer',
            example: 25
        ),
        new OA\Property(
            property: 'card_counts',
            description: 'Detailed card counts and conditions (only when card instances are loaded)',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'card_id', type: 'integer', example: 1),
                    new OA\Property(property: 'card_title', type: 'string', example: 'Lightning Bolt'),
                    new OA\Property(property: 'quantity', type: 'integer', example: 4),
                    new OA\Property(
                        property: 'conditions',
                        type: 'object',
                        example: ['near_mint' => 3, 'lightly_played' => 1],
                        additionalProperties: new OA\AdditionalProperties(type: 'integer')
                    ),
                    new OA\Property(property: 'foil_count', type: 'integer', example: 1),
                    new OA\Property(property: 'non_foil_count', type: 'integer', example: 3),
                ],
                type: 'object'
            )
        ),
    ],
    type: 'object'
)]
class DeckResource extends JsonResource
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
            'format' => $this->format,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships - only include if loaded
            'user' => new UserResource($this->whenLoaded('user')),
            'card_instances' => CardInstanceResource::collection($this->whenLoaded('cardInstances')),

            // Computed attributes
            'total_cards' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->count()
            ),
            'unique_cards' => $this->when(
                $this->relationLoaded('cardInstances'),
                fn() => $this->cardInstances->unique('card_id')->count()
            ),
            'card_counts' => $this->when(
                $this->relationLoaded('cardInstances') && $this->cardInstances->isNotEmpty(),
                fn() => $this->getCardCounts()
            ),
        ];
    }

    /**
     * Get card counts grouped by card ID for deck building.
     */
    private function getCardCounts(): array
    {
        return $this->cardInstances
            ->groupBy('card_id')
            ->map(function ($instances) {
                $card = $instances->first()->card;
                return [
                    'card_id' => $card->id,
                    'card_title' => $card->title,
                    'quantity' => $instances->count(),
                    'conditions' => $instances->groupBy('condition')->map->count(),
                    'foil_count' => $instances->where('foil', true)->count(),
                    'non_foil_count' => $instances->where('foil', false)->count(),
                ];
            })
            ->values()
            ->toArray();
    }
}
