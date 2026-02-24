<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\UserLibraryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Flixarion
|--------------------------------------------------------------------------
*/

// ── Auth (Public) ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ── Content (Public) ──
Route::prefix('contents')->group(function () {
    Route::get('/', [ContentController::class, 'index']);
    Route::get('/search', [ContentController::class, 'search']);
    Route::get('/{id}', [ContentController::class, 'show'])->where('id', '[0-9]+');
});

// ── User Library (Authenticated) ──
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    // Combined library endpoint
    Route::get('/library', [UserLibraryController::class, 'library']);

    // Watchlist — POST to add, DELETE to remove
    Route::get('/watchlist', [UserLibraryController::class, 'watchlist']);
    Route::post('/watchlist', [UserLibraryController::class, 'addToWatchlist']);
    Route::delete('/watchlist/{content_id}', [UserLibraryController::class, 'removeFromWatchlist']);

    // Favorites — POST to add, DELETE to remove
    Route::get('/favorites', [UserLibraryController::class, 'favorites']);
    Route::post('/favorites', [UserLibraryController::class, 'addToFavorites']);
    Route::delete('/favorites/{content_id}', [UserLibraryController::class, 'removeFromFavorites']);

    // Watch History
    Route::get('/history', [UserLibraryController::class, 'history']);
    Route::post('/history', [UserLibraryController::class, 'recordPlay']);
});
