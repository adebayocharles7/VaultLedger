<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\FolioController;
use App\Http\Controllers\Api\v1\RemittanceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| VaultLedger API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
 
    // ── Authentication (public) ───────────────────────────────────────────
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });
 
    // ── Authenticated routes ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
 
        // Auth utilities
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);
 
        // ── Folios (Wallets) ──────────────────────────────────────────────
        Route::post('folios',                           [FolioController::class, 'store']);
        Route::get('folios/{folio}',                    [FolioController::class, 'show']);
        Route::post('folios/{folio}/fund',              [FolioController::class, 'fund']);
        Route::post('folios/{folio}/withdraw',          [FolioController::class, 'withdraw']);
        Route::get('folios/{folio}/ledger',             [FolioController::class, 'ledger']);
 
        // ── Remittances (Transfers) ───────────────────────────────────────
        Route::post('remittances',                      [RemittanceController::class, 'store']);
        Route::get('remittances/{remittance}',          [RemittanceController::class, 'show']);
        Route::post('remittances/{remittance}/reverse', [RemittanceController::class, 'reverse']);
    });
});

