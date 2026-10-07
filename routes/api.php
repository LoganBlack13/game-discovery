<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Companion\MeController;
use App\Http\Controllers\Api\V1\Companion\PairingController;
use App\Http\Middleware\EnsureCompanionDevice;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/companion')->name('api.v1.companion.')->group(function (): void {
    Route::post('/pairings', [PairingController::class, 'store'])
        ->middleware('throttle:companion-pairing')
        ->name('pairings.store');
    Route::post('/pairings/{pairing}/token', [PairingController::class, 'token'])
        ->middleware('throttle:companion-pairing-poll')
        ->name('pairings.token');

    Route::middleware(['auth:sanctum', EnsureCompanionDevice::class, 'throttle:companion-api'])->group(function (): void {
        Route::get('/me', MeController::class)->name('me');
    });
});
