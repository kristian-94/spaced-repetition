<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Decks
    Route::get('/decks', [ApiController::class, 'listDecks']);
    Route::post('/decks', [ApiController::class, 'createDeck']);

    // Cards
    Route::get('/decks/{deck}/cards', [ApiController::class, 'listCards']);
    Route::post('/decks/{deck}/cards', [ApiController::class, 'createCards']);
    Route::put('/cards/{card}', [ApiController::class, 'updateCard']);
    Route::delete('/cards/{card}', [ApiController::class, 'deleteCard']);
    Route::post('/cards/{card}/suspend', [ApiController::class, 'suspendCard']);
});
