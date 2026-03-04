<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameRequestProgressController;
use App\Http\Controllers\Admin\NewsEnrichmentProgressController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');

Route::get('/games/{game:slug}', [App\Http\Controllers\GameController::class, 'show'])->name('games.show');
Route::get('/privacy', fn () => view('pages.privacy'))->name('privacy');
Route::get('/terms', fn () => view('pages.terms'))->name('terms');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/', [DashboardController::class, '__invoke'])->name('dashboard');
        Route::get('/add-game', fn () => view('admin.add-game'))->name('add-game');
        Route::get('/games', [App\Http\Controllers\Admin\GameController::class, 'index'])->name('games.index');
        Route::get('/news-enrichment', fn () => view('admin.news-enrichment'))->name('news-enrichment');
        Route::get('/news-enrichment/progress', [NewsEnrichmentProgressController::class, '__invoke'])->name('news-enrichment.progress');
        Route::get('/game-requests', fn () => view('admin.game-requests'))->name('game-requests');
        Route::get('/game-requests/progress', [GameRequestProgressController::class, '__invoke'])->name('game-requests.progress');
    });
    Route::post('/games/{game:slug}/track', [App\Http\Controllers\GameController::class, 'track'])->name('games.track');
    Route::delete('/games/{game:slug}/track', [App\Http\Controllers\GameController::class, 'untrack'])->name('games.untrack');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/recovery-codes', [App\Http\Controllers\ProfileController::class, 'recoveryCodes'])
        ->middleware('password.confirm')
        ->name('profile.recovery-codes');
});
