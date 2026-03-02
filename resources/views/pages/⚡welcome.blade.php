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
    {{-- Hero: Tonight's picks --}}
    <section
        aria-label="Tonight's picks"
        class="grid gap-8 lg:grid-cols-[minmax(0,2.1fr)_minmax(0,1.1fr)] lg:items-stretch"
    >
        <div class="space-y-4">
            <header class="space-y-2">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary/80">
                    Signal grid arcade
                </p>
                <h1 class="hero-title-glow font-display text-3xl font-semibold leading-tight text-base-content sm:text-4xl">
                    Discover what to play tonight
                </h1>
                <p class="max-w-xl text-sm text-base-content/70">
                    Tune your mood, platform, and session length to get a focused shortlist of games that actually fit your life
                    right now.
                </p>
            </header>

            <div class="mt-4">
                <a
                    href="#trending"
                    class="btn btn-primary btn-sm rounded-full px-5 font-medium"
                >
                    Explore games
                </a>
            </div>

            @if ($this->getHeroPrimary())
                <div class="space-y-4">
                    <x-game.hero-tile
                        :game="$this->getHeroPrimary()"
                        :reasons="[
                            'Popular with players',
                            'Great fit tonight',
                        ]"
                    />

                    @if ($this->getHeroSecondary()->isNotEmpty())
                        <div class="gap-3 flex">
                            @foreach ($this->getHeroSecondary() as $game)
                                <x-game.card
                                    :game="$game"
                                    variant="compact"
                                    :status="$game->release_date?->format('M j, Y')"
                                />
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-base-content/70">
                    We don’t have enough data yet to suggest tonight’s picks. Explore trending and upcoming games below to start
                    building your library.
                </p>
            @endif
        </div>

        <aside class="space-y-3 lg:space-y-4">
            <x-ui.section-header
                title="Your signals"
                subtitle="Backlog, progress & hype"
                class="mb-3 sm:mb-4"
            />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                <x-ui.signal-card
                    :icon="'🎮'"
                    label="Now playing"
                    value="Session planner"
                    tone="info"
                >
                    Quickly jump back into what you started recently.
                </x-ui.signal-card>

                <x-ui.signal-card
                    :icon="'🔥'"
                    label="Backlog heat"
                    value="Hot this week"
                    tone="success"
                >
                    Focused list of games people can’t stop tracking.
                </x-ui.signal-card>

                <x-ui.signal-card
                    :icon="'✨'"
                    label="Hidden gems"
                    value="Shortlist"
                    tone="neutral"
                >
                    Underrated picks you might otherwise scroll past.
                </x-ui.signal-card>
            </div>
        </aside>
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
