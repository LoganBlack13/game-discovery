<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GameDataProvider;
use App\Enums\UserRole;
use App\Models\Game;
use App\Models\User;
use App\Observers\GameObserver;
use App\Services\RawgGameDataProvider;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('accessAdmin', fn (User $user) => $user->role === UserRole::Admin);

        Game::observe(GameObserver::class);
    }
}
