<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GameDataProvider;
use App\Models\Game;
use App\Observers\GameObserver;
use App\Services\RawgGameDataProvider;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('services.rawg.key')) {
            $this->app->bind(GameDataProvider::class, RawgGameDataProvider::class);
        }
    }

    public function boot(): void
    {
        Game::observe(GameObserver::class);
    }
}
