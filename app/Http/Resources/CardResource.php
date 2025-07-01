<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
