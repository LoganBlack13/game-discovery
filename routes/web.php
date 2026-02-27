<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Add dashboard and other protected routes here.
});