<?php

use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Title;

/**
 * Welcome page. Featured hero game = first of getPopularGames() (highest tracked_by_users_count).
 * Changes when track/untrack or data changes; no time-based rotation.
 */
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

    public function getHeroGames()
    {
        return $this->getPopularGames();
    }

    public function getHeroPrimary()
    {
        return $this->getHeroGames()->first();
    }

    public function getHeroSecondary()
    {
        return $this->getHeroGames()->slice(1, 3);
    }

    public function getBacklogGames()
    {
        if (! auth()->check()) {
            return collect();
        }

        return auth()->user()
            ->trackedGames()
            ->with('activities')
            ->latest('tracked_games.created_at')
            ->limit(6)
            ->get();
    }
};
?>

<div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24 space-y-16">
    {{-- Hero: one value prop, optional featured game (most tracked), real CTAs only --}}
    <section
        aria-label="Discover your next game"
        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary/10 via-base-200 to-secondary/10 p-6 sm:p-8 lg:p-10"
    >
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,theme(colors.primary/15),transparent_55%),radial-gradient(circle_at_bottom_right,theme(colors.secondary/15),transparent_55%)]"></div>
        <div class="relative max-w-3xl space-y-6">
            <header class="space-y-2">
                <h1 class="hero-title-glow font-display text-3xl font-semibold leading-tight text-base-content sm:text-4xl">
                    Discover your next game
                </h1>
                <p class="max-w-xl text-sm text-base-content/70">
                    Track what you want to play. Browse coming soon, most tracked, and recently released.
                </p>
            </header>

            <div>
                <a
                    href="#trending"
                    class="btn btn-primary btn-sm rounded-btn px-5 font-medium"
                >
                    Explore games
                </a>
            </div>

            @if ($this->getHeroPrimary())
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary/80">
                        Most tracked on the site — updates as people track games.
                    </p>
                    <x-game.hero-tile :game="$this->getHeroPrimary()" />
                </div>
            @else
                <p class="text-sm text-base-content/70">
                    We don’t have enough data yet to highlight a featured game. Explore coming soon and trending games below to start building your library.
                </p>
            @endif
        </div>
    </section>

    {{-- Coming soon --}}
    <section id="coming-soon" class="mb-16 sm:mb-20" aria-label="Coming soon">
        <x-ui.card-row
            id="coming-soon-row"
            title="Coming soon"
            subtitle="Mark your calendar"
        >
            @foreach($this->getUpcomingGames() as $index => $game)
                <x-game.card
                    :game="$game"
                    :status="$game->release_date?->format('M j, Y') ?? null"
                    class="welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }}"
                />
            @endforeach
        </x-ui.card-row>
    </section>


    {{-- Trending now --}}
    <section id="trending" class="mt-12 sm:mt-16" aria-label="Trending now">
        <x-ui.card-row
            id="trending-row"
            title="Trending now"
            subtitle="What everyone tracks"
        >
            @foreach($this->getPopularGames() as $game)
                <x-game.card :game="$game" status="Trending now" />
            @endforeach
        </x-ui.card-row>
    </section>

    {{-- Your backlog (if signed in) --}}
    <section class="mt-12 sm:mt-16" aria-label="Your backlog">
        <x-ui.section-header
            title="Your backlog"
            subtitle="Games you’re already tracking"
        />
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->getBacklogGames() as $game)
                <x-game.card
                    :game="$game"
                    variant="compact"
                    :status="$game->release_date?->format('M j, Y') ?? 'In your backlog'"
                />
            @empty
                <p class="text-sm text-base-content/70">
                Sign in and start tracking games to see a personalized backlog here.
                </p>
            @endforelse
        </div>
    </section>

    {{-- Recently released --}}
    <section class="mt-12 sm:mt-16" aria-label="Recently released">
        <x-ui.card-row
            id="recently-released-row"
            title="Recently released"
            subtitle="Fresh out of the oven"
        >
            @foreach($this->getRecentlyReleasedGames() as $game)
                <x-game.card :game="$game" />
            @endforeach
        </x-ui.card-row>
    </section>

    {{-- Latest news --}}
    <section id="latest-news" class="mt-12 sm:mt-16" aria-label="Latest news">
        <x-ui.section-header
            id="latest-news-heading"
            title="Latest news"
            subtitle="Updates, patches & events"
            class="mb-4"
        />
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)]">
            <div>
                <livewire:welcome-news />
            </div>
            <aside class="space-y-3">
                <x-ui.signal-card
                    :icon="'📅'"
                    label="Major events"
                    value="Patch notes & showcases"
                    tone="info"
                >
                    Stay ahead of big drops, live events, and seasonal updates.
                </x-ui.signal-card>

                <x-ui.signal-card
                    :icon="'👥'"
                    label="Friends activity"
                    value="Coming soon"
                    tone="neutral"
                >
                    Soon you’ll see what your friends are returning to this week.
                </x-ui.signal-card>
            </aside>
        </div>
    </section>
</div>
