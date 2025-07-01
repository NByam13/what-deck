<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'power' => 'integer',
        'toughness' => 'integer',
    ];

    /**
     * Get all card instances of this card.
     */
    public function cardInstances()
    {
        return $this->hasMany(CardInstance::class);
    }

    /**
     * Get all collections that contain instances of this card.
     */
    public function collections()
    {
        return $this->hasManyThrough(Collection::class, CardInstance::class, 'card_id', 'id', 'id', 'collection_id');
    }

    /**
     * Get all decks that contain instances of this card.
     */
    public function decks()
    {
        return $this->hasManyThrough(Deck::class, CardInstance::class, 'card_id', 'id', 'id', 'deck_id')
                    ->whereNotNull('card_instances.deck_id');
    }
}
