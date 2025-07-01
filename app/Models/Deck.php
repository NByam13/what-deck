<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'format',
    ];

    /**
     * Get the user that owns the deck.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all card instances in this deck.
     */
    public function cardInstances()
    {
        return $this->hasMany(CardInstance::class);
    }

    /**
     * Get all unique cards in this deck.
     */
    public function cards()
    {
        return $this->hasManyThrough(Card::class, CardInstance::class, 'deck_id', 'id', 'id', 'card_id');
    }

    /**
     * Get the total number of cards in this deck.
     */
    public function getTotalCardsAttribute()
    {
        return $this->cardInstances()->count();
    }

    /**
     * Get card count grouped by card.
     */
    public function getCardCounts()
    {
        return $this->cardInstances()
                    ->with('card')
                    ->get()
                    ->groupBy('card_id')
                    ->map(function ($instances) {
                        return [
                            'card' => $instances->first()->card,
                            'count' => $instances->count()
                        ];
                    });
    }
}
