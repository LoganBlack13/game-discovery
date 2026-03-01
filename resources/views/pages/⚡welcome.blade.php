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

<div>
<div class="relative min-h-screen overflow-hidden hero-bg-spec">
    {{-- Particle layer (blur/glow dots) --}}
    <div class="hero-particles pointer-events-none absolute inset-0" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E');" aria-hidden="true"></div>

    {{-- Hero content: max 900–1100px, centered, padding 120–180px --}}
    <div class="relative mx-auto max-w-[1100px] px-4 py-[120px] sm:py-[150px] lg:py-[180px]">
        <div class="flex flex-col items-center text-center">
            <h1 class="hero-title-glow font-display text-[clamp(2rem,8vw,5.5rem)] font-extrabold leading-[1.1] tracking-[1px] text-white opacity-0 welcome-animate welcome-animate-delay-1 md:text-[clamp(3rem,6vw,5.5rem)]">
                <span class="block">DISCOVER</span>
                <span class="block">YOUR NEXT</span>
                <span class="block">GAME.</span>
            </h1>
            <p class="mt-5 max-w-[600px] text-base leading-relaxed text-white/70 opacity-0 welcome-animate welcome-animate-delay-2 sm:text-lg md:text-[18px]">
                Curated picks, hidden gems, and trending titles. One place to find what you'll play next.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-4 opacity-0 welcome-animate welcome-animate-delay-3 sm:flex-row sm:gap-6">
                <a href="#coming-soon" class="btn-hero-primary inline-flex w-full max-w-[280px] items-center justify-center sm:w-auto">
                    Explore games
                </a>
                <a href="#trending" class="btn-hero-secondary inline-block w-full max-w-[280px] text-center sm:w-auto">
                    See what's trending
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Page sections: dark background --}}
<div class="bg-[#0a0f12]">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        {{-- Coming soon --}}
        <section id="coming-soon" class="mb-16 sm:mb-20" aria-label="Coming soon">
            <h2 class="font-display text-sm font-bold uppercase tracking-widest mb-6 text-[#2FD6C4] opacity-0 welcome-animate welcome-animate-delay-4">
                Coming soon
            </h2>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getUpcomingGames() as $index => $game)
                    <div class="opacity-0 welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }}">
                        <x-welcome-game-card :game="$game" :status-label="$game->release_date?->format('M j, Y') ?? null" />
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Trending now --}}
        <section id="trending" class="mt-12 sm:mt-16" aria-label="Trending now">
            <h2 class="font-display text-sm font-bold uppercase tracking-widest mb-6 text-[#2FD6C4]">
                Trending now
            </h2>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getPopularGames() as $game)
                    <x-welcome-game-card :game="$game" status-label="Trending now" />
                @endforeach
            </div>
        </section>

        {{-- Recently released --}}
        <section class="mt-12 sm:mt-16" aria-label="Recently released">
            <h2 class="font-display text-sm font-bold uppercase tracking-widest mb-6 text-[#2FD6C4]">
                Recently released
            </h2>
            <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($this->getRecentlyReleasedGames() as $game)
                    <x-welcome-game-card :game="$game" />
                @endforeach
            </div>
        </section>

        {{-- Latest news --}}
        <section id="latest-news" class="mt-12 sm:mt-16" aria-label="Latest news">
            <livewire:welcome-news />
        </section>
    </div>
</div>
</div>
