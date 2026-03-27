<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameRequestProgressController;
use App\Http\Controllers\Admin\NewsEnrichmentProgressController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameRequestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');
Route::livewire('/games', 'pages::games')->name('games.index');

Route::get('/games/{game:slug}', [GameController::class, 'show'])->name('games.show');
Route::get('/privacy', fn (): Factory|View => view('pages.privacy'))->name('privacy');
Route::get('/terms', fn (): Factory|View => view('pages.terms'))->name('terms');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');
    Route::get('/request-game', [GameRequestController::class, 'index'])->name('game-requests.index');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/', [DashboardController::class, '__invoke'])->name('dashboard');
        Route::get('/add-game', fn (): Factory|View => view('admin.add-game'))->name('add-game');
        Route::get('/games', [App\Http\Controllers\Admin\GameController::class, 'index'])->name('games.index');
        Route::get('/news-enrichment', fn (): Factory|View => view('admin.news-enrichment'))->name('news-enrichment');
        Route::get('/news-enrichment/progress', [NewsEnrichmentProgressController::class, '__invoke'])->name('news-enrichment.progress');
        Route::get('/game-requests', fn (): Factory|View => view('admin.game-requests'))->name('game-requests');
        Route::get('/game-requests/progress', [GameRequestProgressController::class, '__invoke'])->name('game-requests.progress');
    });
    Route::post('/games/{game:slug}/track', [GameController::class, 'track'])->name('games.track');
    Route::delete('/games/{game:slug}/track', [GameController::class, 'untrack'])->name('games.untrack');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/recovery-codes', [ProfileController::class, 'recoveryCodes'])
        ->middleware('password.confirm')
        ->name('profile.recovery-codes');
});
