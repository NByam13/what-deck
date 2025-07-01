<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
