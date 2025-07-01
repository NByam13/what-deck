<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
