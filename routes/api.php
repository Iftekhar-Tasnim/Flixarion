<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\ScanResultController;
use App\Http\Controllers\SourceHealthController;
use App\Http\Controllers\UserLibraryController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrichmentController;
use App\Http\Controllers\Admin\ReviewQueueController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SourceController as AdminSourceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

// ── CORS Proxy (Public — Story #82, BR-06.12) ──
// Fetches BDIX source URLs server-side so the browser can read directory listings
// without CORS restrictions. Whitelisted to registered source base_urls only.
Route::get('/proxy', [ProxyController::class, 'fetch']);

// ── Sources (Public — for Race Strategy + Client Scan) ──
Route::prefix('sources')->group(function () {
    Route::get('/', [SourceHealthController::class, 'index']);
    Route::get('/{id}/ping', [SourceHealthController::class, 'ping'])->where('id', '[0-9]+');
    Route::post('/health-report', [SourceHealthController::class, 'store']);
    Route::post('/{id}/scan-results', [ScanResultController::class, 'store'])->where('id', '[0-9]+');
});

// ── User Library (Authenticated) ──
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/library', [UserLibraryController::class, 'library']);

    Route::get('/watchlist', [UserLibraryController::class, 'watchlist']);
    Route::post('/watchlist', [UserLibraryController::class, 'addToWatchlist']);
    Route::delete('/watchlist/{content_id}', [UserLibraryController::class, 'removeFromWatchlist']);

    Route::get('/favorites', [UserLibraryController::class, 'favorites']);
    Route::post('/favorites', [UserLibraryController::class, 'addToFavorites']);
    Route::delete('/favorites/{content_id}', [UserLibraryController::class, 'removeFromFavorites']);

    Route::get('/history', [UserLibraryController::class, 'history']);
    Route::post('/history', [UserLibraryController::class, 'recordPlay']);
});

// ── Admin (Authenticated + Admin Role) ──
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Sources CRUD
    Route::prefix('sources')->group(function () {
        Route::get('/', [AdminSourceController::class, 'index']);
        Route::post('/', [AdminSourceController::class, 'store']);
        Route::get('/{id}', [AdminSourceController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/{id}', [AdminSourceController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/{id}', [AdminSourceController::class, 'destroy'])->where('id', '[0-9]+');
        Route::get('/test-all', [AdminSourceController::class, 'testAllConnections']);
        Route::post('/scan-all', [AdminSourceController::class, 'scanAll']);
        Route::post('/{id}/test', [AdminSourceController::class, 'testConnection'])->where('id', '[0-9]+');
        Route::post('/{id}/scan', [AdminSourceController::class, 'triggerScan'])->where('id', '[0-9]+');
    });

    // Content Management
    Route::prefix('contents')->group(function () {
        Route::get('/', [AdminContentController::class, 'index']);
        Route::patch('/{id}', [AdminContentController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/{id}', [AdminContentController::class, 'destroy'])->where('id', '[0-9]+');
        Route::post('/{id}/resync', [AdminContentController::class, 'resync'])->where('id', '[0-9]+');
    });

    // Review Queue
    Route::prefix('review-queue')->group(function () {
        Route::get('/', [ReviewQueueController::class, 'index']);
        Route::post('/{id}/approve', [ReviewQueueController::class, 'approve'])->where('id', '[0-9]+');
        Route::post('/{id}/correct', [ReviewQueueController::class, 'correct'])->where('id', '[0-9]+');
        Route::post('/{id}/reject', [ReviewQueueController::class, 'reject'])->where('id', '[0-9]+');
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/{id}/ban', [AdminUserController::class, 'ban'])->where('id', '[0-9]+');
        Route::post('/{id}/unban', [AdminUserController::class, 'unban'])->where('id', '[0-9]+');
        Route::post('/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->where('id', '[0-9]+');
    });

    // Enrichment
    Route::prefix('enrichment')->group(function () {
        Route::get('/', [EnrichmentController::class, 'status']);
        Route::post('/pause', [EnrichmentController::class, 'pause']);
        Route::post('/resume', [EnrichmentController::class, 'resume']);
        Route::post('/retry-pending', [EnrichmentController::class, 'retryPending']);
        Route::post('/retry-unmatched', [EnrichmentController::class, 'retryUnmatched']);
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);
});
