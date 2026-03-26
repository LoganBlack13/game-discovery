<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver as GameDataProviderResolverContract;
use App\Enums\UserRole;
use App\Models\Game;
use App\Models\User;
use App\Observers\GameObserver;
use App\Services\GameDataProviderResolver;
use App\Services\RawgGameDataProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('services.rawg.key')) { // @codeCoverageIgnore
            $this->app->bind(GameDataProvider::class, RawgGameDataProvider::class); // @codeCoverageIgnore
        }

        $this->app->bind(GameDataProviderResolverContract::class, GameDataProviderResolver::class);
    }

    public function boot(): void
    {
        Gate::define('accessAdmin', fn (User $user): bool => $user->role === UserRole::Admin);

        Game::observe(GameObserver::class);
    }
}
