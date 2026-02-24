<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Flixarion
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api (configured in bootstrap/app.php).
|
*/

// ── Auth (Public) ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ── Public Content Routes (placeholder for Phase 2) ──
// Route::prefix('contents')->group(function () { ... });

// ── Authenticated User Routes (placeholder for Phase 2) ──
// Route::middleware('auth:sanctum')->prefix('user')->group(function () { ... });

// ── Admin Routes (placeholder for Phase 2) ──
// Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () { ... });
