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

    public function getHeroMoods(): array
    {
        return [
            'cozy' => 'Cozy',
            'story' => 'Story-heavy',
            'competitive' => 'Competitive',
            'roguelike' => 'Roguelike',
        ];
    }

    public function getHeroSessionLengths(): array
    {
        return [
            'short' => 'Up to 30 min',
            'medium' => '1–2 hours',
            'long' => 'Long session',
        ];
    }

    public function getHeroPlatforms(): array
    {
        return [
            'PC' => 'PC',
            'PlayStation' => 'PlayStation',
            'Xbox' => 'Xbox',
            'Switch' => 'Switch',
        ];
    }

    public function getMoodGames(): array
    {
        $base = Game::query()
            ->withCount('trackedByUsers')
            ->orderByDesc('tracked_by_users_count')
            ->limit(30)
            ->get();

        $groups = [
            'cozy' => fn (Game $game): bool => in_array('Indie', $game->genres, true) || in_array('Simulation', $game->genres, true),
            'story' => fn (Game $game): bool => in_array('RPG', $game->genres, true) || in_array('Adventure', $game->genres, true),
            'competitive' => fn (Game $game): bool => in_array('Shooter', $game->genres, true) || in_array('Multiplayer', $game->genres, true),
            'roguelike' => fn (Game $game): bool => in_array('Roguelike', $game->genres, true) || in_array('Roguelite', $game->genres, true),
        ];

        $result = [];

        foreach ($groups as $key => $filter) {
            $result[$key] = $base->filter($filter)->take(6);
        }

        return $result;
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

    public function getPlatformSpotlights(): array
    {
        $platforms = ['PC', 'PlayStation', 'Xbox', 'Switch'];

        $result = [];

        foreach ($platforms as $platform) {
            $result[$platform] = Game::query()
                ->whereJsonContains('platforms', $platform)
                ->withCount('trackedByUsers')
                ->orderByDesc('tracked_by_users_count')
                ->limit(8)
                ->get();
        }

        return $result;
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

            <div class="flex flex-wrap gap-2">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($this->getHeroMoods() as $value => $label)
                        <span
                            class="badge badge-outline border-primary/40 px-3 py-2 text-[11px] font-medium text-base-content/80"
                        >
                            {{ $label }}
                        </span>
                    @endforeach
                </div>

                <div class="hidden flex-wrap gap-1.5 sm:flex">
                    @foreach ($this->getHeroSessionLengths() as $label)
                        <span class="badge badge-outline border-base-content/20 px-3 py-2 text-[11px] font-medium text-base-content/70">
                            {{ $label }}
                        </span>
                    @endforeach
                </div>

                <div class="hidden flex-wrap gap-1.5 md:flex">
                    @foreach ($this->getHeroPlatforms() as $label)
                        <span class="badge badge-outline border-base-content/20 px-3 py-2 text-[11px] font-medium text-base-content/70">
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
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

                    @php($heroSecondary = $this->getHeroSecondary())
                    @if ($heroSecondary->isNotEmpty())
                        <div class="hidden gap-3 sm:grid sm:grid-cols-3">
                            @foreach ($heroSecondary as $game)
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
        <x-ui.section-header
            id="coming-soon-heading"
            title="Coming soon"
            subtitle="Mark your calendar"
            class="mb-6"
        />
        <div class="flex flex-nowrap gap-4 overflow-x-auto p-4">
            @foreach($this->getUpcomingGames() as $index => $game)
                <div class="opacity-0 welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }}">
                    <x-game.card :game="$game" :status="$game->release_date?->format('M j, Y') ?? null" />
                </div>
            @endforeach
        </div>
    </section>

    {{-- Discover by mood --}}
    <section class="mt-4 space-y-6" aria-label="Discover by mood">
        <x-ui.section-header
            title="Discover by mood"
            subtitle="Cozy nights, big stories, or quick runs"
        />

        @php($moodGroups = $this->getMoodGames())
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($this->getHeroMoods() as $key => $label)
                @php($games = $moodGroups[$key] ?? collect())
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-base-content">
                            {{ $label }}
                        </p>
                        <span class="badge badge-ghost badge-sm text-[11px] text-base-content/70">
                            Mood rail
                        </span>
                    </div>
                    @if ($games->isEmpty())
                        <p class="text-xs text-base-content/60">
                            We’ll surface {{ strtolower($label) }} picks here as you and others track more games.
                        </p>
                    @else
                        <div class="flex gap-3 overflow-x-auto pb-1">
                            @foreach ($games as $game)
                                <x-game.card
                                    :game="$game"
                                    variant="compact"
                                    :status="$game->release_date?->format('M j, Y')"
                                />
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Trending now --}}
    <section id="trending" class="mt-12 sm:mt-16" aria-label="Trending now">
        <x-ui.section-header
            id="trending-heading"
            title="Trending now"
            subtitle="What everyone tracks"
            class="mb-6"
        />
        <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach($this->getPopularGames() as $game)
                <x-game.card :game="$game" status="Trending now" />
            @endforeach
        </div>
    </section>

    {{-- Your backlog (if signed in) --}}
    <section class="mt-12 sm:mt-16" aria-label="Your backlog">
        <x-ui.section-header
            title="Your backlog"
            subtitle="Games you’re already tracking"
        />

        @php($backlog = $this->getBacklogGames())
        @if ($backlog->isEmpty())
            <p class="text-sm text-base-content/70">
                Sign in and start tracking games to see a personalized backlog here.
            </p>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($backlog as $game)
                    <x-game.card
                        :game="$game"
                        variant="compact"
                        :status="$game->release_date?->format('M j, Y') ?? 'In your backlog'"
                    />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Recently released --}}
    <section class="mt-12 sm:mt-16" aria-label="Recently released">
        <x-ui.section-header
            id="recently-released-heading"
            title="Recently released"
            subtitle="Fresh out of the oven"
            class="mb-6"
        />
        <div class="flex flex-nowrap gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach($this->getRecentlyReleasedGames() as $game)
                <x-game.card :game="$game" />
            @endforeach
        </div>
    </section>

    {{-- Platform spotlights --}}
    <section class="mt-12 sm:mt-16" aria-label="Platform spotlights">
        <x-ui.section-header
            title="Platform spotlights"
            subtitle="Highlights from each library"
        />

        @php($spotlights = $this->getPlatformSpotlights())
        <div class="grid gap-8 lg:grid-cols-2">
            @foreach ($spotlights as $platform => $games)
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-base-content">
                            {{ $platform }}
                        </p>
                        <span class="badge badge-outline badge-sm text-[11px] text-base-content/70">
                            Popular on {{ $platform }}
                        </span>
                    </div>
                    @if ($games->isEmpty())
                        <p class="text-xs text-base-content/60">
                            No spotlight games yet for {{ $platform }}. Check back soon.
                        </p>
                    @else
                        <div class="flex gap-3 overflow-x-auto pb-1">
                            @foreach ($games as $game)
                                <x-game.card
                                    :game="$game"
                                    variant="compact"
                                    :status="$game->release_date?->format('M j, Y')"
                                />
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
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
