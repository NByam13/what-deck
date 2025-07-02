<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardInstanceController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\ImportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Card template routes
Route::apiResource('cards', CardController::class);

// Card instance routes
Route::apiResource('card-instances', CardInstanceController::class);
Route::put('card-instances/{cardInstance}/move-to-deck/{deck}', [CardInstanceController::class, 'moveToDeck']);
Route::put('card-instances/{cardInstance}/remove-from-deck', [CardInstanceController::class, 'removeFromDeck']);

// Collection routes
Route::apiResource('collections', CollectionController::class);
Route::get('collections/{collection}/card-instances', [CollectionController::class, 'cardInstances']);

// Deck routes
Route::apiResource('decks', DeckController::class);
Route::get('decks/{deck}/card-instances', [DeckController::class, 'cardInstances']);
Route::post('decks/{deck}/add-card-instance/{cardInstance}', [DeckController::class, 'addCardInstance']);
Route::delete('decks/{deck}/remove-card-instance/{cardInstance}', [DeckController::class, 'removeCardInstance']);

// Import routes
Route::get('import/formats', [ImportController::class, 'supportedFormats']);
Route::post('collections/{collection}/import/moxfield', [ImportController::class, 'moxfield']); 