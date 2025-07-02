<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CardInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'collection_id',
        'deck_id',
        'condition',
        'foil',
        'language',
        'tags',
        'purchase_price',
        'alter',
        'proxy',
    ];

    protected $casts = [
        'foil' => 'boolean',
        'alter' => 'boolean',
        'proxy' => 'boolean',
        'tags' => 'array',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Get the card that this instance belongs to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the collection that this instance belongs to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the deck that this instance belongs to.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Scope to get instances that are available (not in a deck).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('deck_id');
    }

    /**
     * Scope to get instances that are in a deck.
     */
    public function scopeInDeck(Builder $query): Builder
    {
        return $query->whereNotNull('deck_id');
    }

    /**
     * Check if this instance is in a deck.
     */
    public function isInDeck(): bool
    {
        return !is_null($this->deck_id);
    }

    /**
     * Check if this instance is available (not in a deck).
     */
    public function isAvailable(): bool
    {
        return is_null($this->deck_id);
    }
}
