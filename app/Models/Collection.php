<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the collection.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all card instances in this collection.
     */
    public function cardInstances()
    {
        return $this->hasMany(CardInstance::class);
    }

    /**
     * Get all unique cards in this collection.
     */
    public function cards()
    {
        return $this->hasManyThrough(Card::class, CardInstance::class, 'collection_id', 'id', 'id', 'card_id');
    }

    /**
     * Get card instances that are not assigned to any deck.
     */
    public function unassignedCardInstances()
    {
        return $this->cardInstances()->whereNull('deck_id');
    }
}
