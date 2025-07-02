<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'description',
        'cost',
        'type',
        'subtype',
        'power',
        'toughness',
        'edition',
        'collector_number',
    ];

    protected $casts = [
        'power' => 'integer',
        'toughness' => 'integer',
    ];

    /**
     * Get all card instances for this card.
     */
    public function cardInstances(): HasMany
    {
        return $this->hasMany(CardInstance::class);
    }

    /**
     * Get collections that contain this card.
     */
    public function collections()
    {
        return $this->hasManyThrough(Collection::class, CardInstance::class, 'card_id', 'id', 'id', 'collection_id');
    }

    /**
     * Get decks that contain this card.
     */
    public function decks()
    {
        return $this->hasManyThrough(Deck::class, CardInstance::class, 'card_id', 'id', 'id', 'deck_id')
                    ->whereNotNull('card_instances.deck_id');
    }
}
