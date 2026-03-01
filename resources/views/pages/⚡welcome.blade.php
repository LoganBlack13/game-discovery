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

{{-- Page sections (full-page background from layout) --}}
<div>
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        {{-- Coming soon --}}
        <section id="coming-soon" class="mb-16 sm:mb-20" aria-label="Coming soon">
            <h2 class="font-display text-sm font-bold uppercase tracking-widest mb-6 text-[#2FD6C4] opacity-0 welcome-animate welcome-animate-delay-4">
                Coming soon
            </h2>
            <div class="flex flex-nowrap gap-4 overflow-x-auto p-4">
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
