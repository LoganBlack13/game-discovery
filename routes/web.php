<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');

Route::get('/games/{game:slug}', [App\Http\Controllers\GameController::class, 'show'])->name('games.show');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/', [DashboardController::class, '__invoke'])->name('dashboard');
    });
    Route::post('/games/{game:slug}/track', [App\Http\Controllers\GameController::class, 'track'])->name('games.track');
    Route::delete('/games/{game:slug}/track', [App\Http\Controllers\GameController::class, 'untrack'])->name('games.untrack');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/recovery-codes', [App\Http\Controllers\ProfileController::class, 'recoveryCodes'])
        ->middleware('password.confirm')
        ->name('profile.recovery-codes');
});
