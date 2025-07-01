<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CardInstance',
    title: 'Card Instance Resource',
    description: 'Individual physical card instance resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'card_id', type: 'integer', example: 1),
        new OA\Property(property: 'collection_id', type: 'integer', example: 1),
        new OA\Property(property: 'deck_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'condition', type: 'string', enum: ['mint', 'near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'], example: 'near_mint'),
        new OA\Property(property: 'foil', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T12:00:00.000000Z'),
        new OA\Property(
            property: 'card',
            ref: '#/components/schemas/Card',
            description: 'The card template (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'collection',
            ref: '#/components/schemas/Collection',
            description: 'The collection this instance belongs to (only when relationship is loaded)'
        ),
        new OA\Property(
            property: 'deck',
            ref: '#/components/schemas/Deck',
            description: 'The deck this instance is assigned to (only when relationship is loaded)'
        ),
        new OA\Property(property: 'is_in_deck', type: 'boolean', description: 'Whether this instance is assigned to a deck', example: true),
        new OA\Property(property: 'is_available', type: 'boolean', description: 'Whether this instance is available (not in a deck)', example: false),
        new OA\Property(property: 'condition_description', type: 'string', description: 'Human-readable condition description', example: 'Near Mint (NM)'),
    ],
    type: 'object'
)]
class CardInstanceResource extends JsonResource
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
            'card_id' => $this->card_id,
            'collection_id' => $this->collection_id,
            'deck_id' => $this->deck_id,
            'condition' => $this->condition,
            'foil' => $this->foil,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships - only include if loaded
            'card' => new CardResource($this->whenLoaded('card')),
            'collection' => new CollectionResource($this->whenLoaded('collection')),
            'deck' => new DeckResource($this->whenLoaded('deck')),

            // Computed attributes
            'is_in_deck' => !is_null($this->deck_id),
            'is_available' => is_null($this->deck_id),
            'condition_description' => $this->getConditionDescription(),
        ];
    }

    /**
     * Get a human-readable description of the card condition.
     */
    private function getConditionDescription(): string
    {
        return match($this->condition) {
            'mint' => 'Mint (M)',
            'near_mint' => 'Near Mint (NM)',
            'lightly_played' => 'Lightly Played (LP)',
            'moderately_played' => 'Moderately Played (MP)',
            'heavily_played' => 'Heavily Played (HP)',
            'damaged' => 'Damaged (DMG)',
            default => ucfirst(str_replace('_', ' ', $this->condition ?? 'unknown'))
        };
    }
}
