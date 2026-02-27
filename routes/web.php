<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/recovery-codes', [App\Http\Controllers\ProfileController::class, 'recoveryCodes'])
        ->middleware('password.confirm')
        ->name('profile.recovery-codes');
});
