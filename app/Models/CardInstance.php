<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'collection_id',
        'deck_id',
        'condition',
        'foil',
    ];

    protected $casts = [
        'foil' => 'boolean',
    ];

    /**
     * Get the card that this instance represents.
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the collection that owns this card instance.
     */
    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the deck that this card instance belongs to (if any).
     */
    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Check if this card instance is assigned to a deck.
     */
    public function isInDeck()
    {
        return !is_null($this->deck_id);
    }

    /**
     * Check if this card instance is available (not in a deck).
     */
    public function isAvailable()
    {
        return is_null($this->deck_id);
    }

    /**
     * Scope to get only card instances that are not in any deck.
     */
    public function scopeAvailable($query)
    {
        return $query->whereNull('deck_id');
    }

    /**
     * Scope to get only card instances that are in a deck.
     */
    public function scopeInDeck($query)
    {
        return $query->whereNotNull('deck_id');
    }
}
