<?php

use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Discover your next game')] class extends Component
{
    public function getUpcomingGames()
    {
        return Game::query()
            ->upcoming()
            ->upcomingByReleaseDate()
            ->limit(12)
            ->get();
    }

    public function getPopularGames()
    {
        return Game::query()
            ->withCount('trackedByUsers')
            ->orderByDesc('tracked_by_users_count')
            ->limit(12)
            ->get();
    }

    public function getRecentlyReleasedGames()
    {
        return Game::query()
            ->released()
            ->orderByDesc('release_date')
            ->limit(12)
            ->get();
    }
};
?>

<div class="relative min-h-[calc(100vh-3.5rem)] overflow-hidden">
    {{-- Background: gradient mesh + grain --}}
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,oklch(0.72_0.15_195_/_.15),transparent),radial-gradient(ellipse_60%_50%_at_80%_50%,oklch(0.45_0.12_270_/_.12),transparent),radial-gradient(ellipse_50%_40%_at_20%_80%,oklch(0.5_0.1_195_/_.1),transparent)] dark:bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,oklch(0.72_0.15_195_/_.2),transparent),radial-gradient(ellipse_60%_50%_at_80%_50%,oklch(0.35_0.1_270_/_.15),transparent),radial-gradient(ellipse_50%_40%_at_20%_80%,oklch(0.4_0.08_195_/_.12),transparent)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.025] dark:opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E');" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-24 lg:px-8 lg:pt-32">
        {{-- Hero: asymmetric layout, headline left, CTA right on large screens --}}
        <div class="grid gap-12 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-16">
            <div class="space-y-8">
                <h1 class="font-display text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-5xl md:text-6xl lg:text-7xl opacity-0 welcome-animate welcome-animate-delay-1 [text-shadow:0_0_80px_oklch(0.72_0.15_195_/_.4)] dark:[text-shadow:0_0_80px_oklch(0.72_0.15_195_/_.5)]">
                    Discover your next game
                </h1>
                <p class="max-w-xl font-sans text-lg text-zinc-600 dark:text-zinc-400 sm:text-xl opacity-0 welcome-animate welcome-animate-delay-2">
                    Curated picks, hidden gems, and trending titles. One place to find what you’ll play next.
                </p>
                <div class="flex flex-wrap gap-4 opacity-0 welcome-animate welcome-animate-delay-3">
                    <flux:button href="#coming-soon" variant="primary" color="cyan" size="base" class="focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950 transition-transform hover:scale-[1.02] active:scale-[0.98]">
                        Explore games
                    </flux:button>
                    <flux:button href="#trending" variant="ghost" size="base" class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950">
                        See what’s trending
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Coming soon --}}
        <section id="coming-soon" class="mt-20 sm:mt-28 lg:mt-36" aria-label="Coming soon">
            <p class="font-display text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 opacity-0 welcome-animate welcome-animate-delay-4 mb-6">
                Coming soon
            </p>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getUpcomingGames() as $index => $game)
                    <flux:card
                        class="welcome-game-card shrink-0 w-[280px] min-w-[280px] max-w-[280px] opacity-0 welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }} border-zinc-200/80 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-sm focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-zinc-950"
                        size="sm"
                    >
                        <a href="{{ route('games.show', $game) }}" class="block min-w-0 focus:outline-none">
                            @if ($game->cover_image)
                                <div class="aspect-[3/4] w-full overflow-hidden rounded-lg mb-3">
                                    <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                                </div>
                            @else
                                <div class="aspect-[3/4] rounded-lg bg-gradient-to-br from-zinc-200 to-zinc-300 dark:from-zinc-700 dark:to-zinc-800 mb-3 flex items-center justify-center">
                                    <span class="font-display text-2xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                                </div>
                            @endif
                            <h3 class="font-display font-semibold text-zinc-900 dark:text-white">{{ $game->title }}</h3>
                            @if ($game->release_date)
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $game->release_date->format('M j, Y') }}</p>
                            @endif
                            @if (count($game->platforms) > 0)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($game->platforms, 0, 2)) }}</p>
                            @endif
                        </a>
                    </flux:card>
                @endforeach
            </div>
        </section>

        {{-- Trending now --}}
        <section id="trending" class="mt-12 sm:mt-16" aria-label="Trending now">
            <p class="font-display text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 mb-6">
                Trending now
            </p>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getPopularGames() as $index => $game)
                    <flux:card
                        class="welcome-game-card shrink-0 w-[280px] min-w-[280px] max-w-[280px] border-zinc-200/80 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-sm focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-zinc-950"
                        size="sm"
                    >
                        <a href="{{ route('games.show', $game) }}" class="block min-w-0 focus:outline-none">
                            @if ($game->cover_image)
                                <div class="aspect-[3/4] w-full overflow-hidden rounded-lg mb-3">
                                    <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                                </div>
                            @else
                                <div class="aspect-[3/4] rounded-lg bg-gradient-to-br from-zinc-200 to-zinc-300 dark:from-zinc-700 dark:to-zinc-800 mb-3 flex items-center justify-center">
                                    <span class="font-display text-2xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                                </div>
                            @endif
                            <h3 class="font-display font-semibold text-zinc-900 dark:text-white">{{ $game->title }}</h3>
                            @if ($game->release_date)
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $game->release_date->format('M j, Y') }}</p>
                            @endif
                        </a>
                    </flux:card>
                @endforeach
            </div>
        </section>

        {{-- Recently released --}}
        <section class="mt-12 sm:mt-16" aria-label="Recently released">
            <p class="font-display text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 mb-6">
                Recently released
            </p>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getRecentlyReleasedGames() as $index => $game)
                    <flux:card
                        class="welcome-game-card shrink-0 w-[280px] min-w-[280px] max-w-[280px] border-zinc-200/80 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-sm focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-zinc-950"
                        size="sm"
                    >
                        <a href="{{ route('games.show', $game) }}" class="block min-w-0 focus:outline-none">
                            @if ($game->cover_image)
                                <div class="aspect-[3/4] w-full overflow-hidden rounded-lg mb-3">
                                    <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                                </div>
                            @else
                                <div class="aspect-[3/4] rounded-lg bg-gradient-to-br from-zinc-200 to-zinc-300 dark:from-zinc-700 dark:to-zinc-800 mb-3 flex items-center justify-center">
                                    <span class="font-display text-2xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                                </div>
                            @endif
                            <h3 class="font-display font-semibold text-zinc-900 dark:text-white">{{ $game->title }}</h3>
                            @if ($game->release_date)
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $game->release_date->format('M j, Y') }}</p>
                            @endif
                        </a>
                    </flux:card>
                @endforeach
            </div>
        </section>

        {{-- Latest news --}}
        <section class="mt-12 sm:mt-16" aria-label="Latest news">
            <livewire:welcome-news />
        </section>
    </div>
</div>
