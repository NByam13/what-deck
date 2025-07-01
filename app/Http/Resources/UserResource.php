<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
