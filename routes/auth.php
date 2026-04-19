<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.redirect');

    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
