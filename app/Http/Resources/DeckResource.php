<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
