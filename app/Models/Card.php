<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        // Original fields (actual column names from database)
        'title', 'image_url', 'description', 'cost', 'type', 'subtype', 'power', 'toughness',
        
        // Added fields for compatibility
        'edition', 'collector_number', 'image',
        
        // Scryfall Identifiers
        'scryfall_id', 'oracle_id', 'multiverse_ids', 'mtgo_id', 'mtgo_foil_id',
        'arena_id', 'tcgplayer_id', 'tcgplayer_etched_id', 'cardmarket_id',
        
        // Core Game Data
        'mana_cost', 'cmc', 'oracle_text', 'flavor_text', 'type_line',
        
        // Colors and Identity
        'colors', 'color_identity', 'color_indicator', 'keywords', 'produced_mana',
        
        // Stats and Attributes
        'loyalty', 'defense', 'hand_modifier', 'life_modifier',
        
        // Legalities and Rankings
        'legalities', 'edhrec_rank', 'penny_rank',
        
        // Set and Printing Info
        'set', 'set_id', 'set_name', 'set_type', 'rarity', 'released_at', 'lang',
        
        // Visual and Physical Properties
        'image_uris', 'layout', 'highres_image', 'image_status', 'border_color',
        'frame', 'frame_effects', 'security_stamp', 'watermark',
        
        // Artist Information
        'artist', 'artist_ids', 'illustration_id',
        
        // Boolean Flags
        'reserved', 'foil', 'nonfoil', 'oversized', 'promo', 'reprint', 'variation',
        'digital', 'full_art', 'textless', 'booster', 'story_spotlight', 'game_changer',
        
        // Additional Data
        'finishes', 'games', 'promo_types', 'prices', 'purchase_uris', 'related_uris',
        'variation_of', 'card_back_id',
        
        // URIs
        'scryfall_uri', 'uri', 'rulings_uri', 'prints_search_uri'
    ];

    protected $casts = [
        // Note: power/toughness stored as strings to handle "*", "X", "1+*", etc.
        
        // JSON fields
        'multiverse_ids' => 'array',
        'colors' => 'array',
        'color_identity' => 'array',
        'color_indicator' => 'array',
        'keywords' => 'array',
        'produced_mana' => 'array',
        'legalities' => 'array',
        'image_uris' => 'array',
        'artist_ids' => 'array',
        'finishes' => 'array',
        'games' => 'array',
        'promo_types' => 'array',
        'prices' => 'array',
        'purchase_uris' => 'array',
        'related_uris' => 'array',
        
        // Boolean fields
        'highres_image' => 'boolean',
        'reserved' => 'boolean',
        'foil' => 'boolean',
        'nonfoil' => 'boolean',
        'oversized' => 'boolean',
        'promo' => 'boolean',
        'reprint' => 'boolean',
        'variation' => 'boolean',
        'digital' => 'boolean',
        'full_art' => 'boolean',
        'textless' => 'boolean',
        'booster' => 'boolean',
        'story_spotlight' => 'boolean',
        'game_changer' => 'boolean',
        
        // Date fields
        'released_at' => 'date',
        
        // Decimal fields
        'cmc' => 'decimal:2',
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

    // Scope for filtering by colors
    public function scopeByColors($query, array $colors)
    {
        return $query->whereJsonContains('colors', $colors);
    }

    // Scope for filtering by color identity
    public function scopeByColorIdentity($query, array $colorIdentity)
    {
        return $query->whereJsonContains('color_identity', $colorIdentity);
    }

    // Scope for filtering by format legality
    public function scopeLegalIn($query, string $format)
    {
        return $query->whereJsonContains('legalities->' . $format, 'legal');
    }

    // Scope for filtering by set
    public function scopeInSet($query, string $setCode)
    {
        return $query->where('set', $setCode);
    }

    // Scope for filtering by rarity
    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    // Scope for filtering by mana value range
    public function scopeByCmcRange($query, float $min = null, float $max = null)
    {
        if ($min !== null) {
            $query->where('cmc', '>=', $min);
        }
        if ($max !== null) {
            $query->where('cmc', '<=', $max);
        }
        return $query;
    }

    // Get display name (prioritize title over name)
    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: $this->name ?: 'Unknown Card';
    }

    // Get main image URL
    public function getMainImageAttribute(): ?string
    {
        if ($this->image_uris && isset($this->image_uris['normal'])) {
            return $this->image_uris['normal'];
        }
        return $this->image;
    }

    // Check if card is legal in format
    public function isLegalIn(string $format): bool
    {
        return isset($this->legalities[$format]) && $this->legalities[$format] === 'legal';
    }

    // Get formatted mana cost
    public function getFormattedManaCostAttribute(): string
    {
        return $this->mana_cost ?: '';
    }
}
