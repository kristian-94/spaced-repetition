<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('decks.index');
    }

    return Inertia::render('Landing');
})->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Public API documentation (no auth — agents / prospective users can read it).
Route::get('/docs/api', function () {
    return Inertia::render('Docs/Api', [
        'baseUrl' => rtrim(config('app.url'), '/'),
    ]);
})->name('docs.api');

// Public explainer: what is spaced repetition?
Route::get('/learn', function () {
    return Inertia::render('Learn');
})->name('learn');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect /dashboard to decks
    Route::get('/dashboard', function () {
        return redirect()->route('decks.index');
    })->name('dashboard');

    // Decks
    Route::get('/decks', [DeckController::class, 'index'])->name('decks.index');
    Route::post('/decks', [DeckController::class, 'store'])->name('decks.store');
    Route::patch('/decks/{deck}', [DeckController::class, 'update'])->name('decks.update');
    Route::delete('/decks/{deck}', [DeckController::class, 'destroy'])->name('decks.destroy');
    Route::patch('/decks/{deck}/toggle-active', [DeckController::class, 'toggleActive'])->name('decks.toggleActive');
    Route::post('/decks/reorder', [DeckController::class, 'reorder'])->name('decks.reorder');

    // Cards
    Route::get('/decks/{deck}/cards', [CardController::class, 'index'])->name('cards.index');
    Route::post('/decks/{deck}/cards', [CardController::class, 'store'])->name('cards.store');
    Route::patch('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::patch('/cards/{card}/suspend', [CardController::class, 'suspend'])->name('cards.suspend');

    // Review
    Route::get('/decks/{deck}/review', [ReviewController::class, 'index'])->name('review.index');
    Route::post('/decks/{deck}/review', [ReviewController::class, 'submit'])->name('review.submit');
    Route::get('/review/all', [ReviewController::class, 'all'])->name('review.all');
    Route::post('/review/all', [ReviewController::class, 'submitAll'])->name('review.submitAll');
    Route::post('/review/boost', [ReviewController::class, 'boost'])->name('review.boost');

    // Settings
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/token', [SettingsController::class, 'generateToken'])->name('settings.token.generate');
    Route::delete('/settings/token/{tokenId}', [SettingsController::class, 'revokeToken'])->name('settings.token.revoke');
    Route::post('/settings/test-notification', [SettingsController::class, 'testNotification'])->name('settings.test-notification');

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Telegram webhook (no auth, secured by token in URL or secret)
Route::post('/telegram/webhook', [NotificationController::class, 'telegramWebhook'])->name('telegram.webhook');

require __DIR__.'/auth.php';
