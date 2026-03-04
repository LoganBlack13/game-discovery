<?php

use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Title;

/**
 * Welcome page. Featured hero game = first of getPopularGames() (highest tracked_by_users_count).
 * Changes when track/untrack or data changes; no time-based rotation.
 */
new #[Title('Track your games')] class extends Component
{
    public function getUpcomingGames()
    {
        return Game::query()
            ->upcoming()
            ->upcomingByReleaseDate()
            ->withCount('news')
            ->limit(6)
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
    {{-- Hero: product value and dashboard preview --}}
    <section
        aria-label="Track your games"
        class="hero relative overflow-hidden rounded-box bg-gradient-to-r from-warning/20 via-secondary/30 to-secondary/50 px-4 py-8 sm:px-6 sm:py-10 lg:px-10 lg:py-12"
    >
        <div class="hero-content relative flex flex-col gap-8 lg:flex-row lg:items-stretch">
            <div class="max-w-xl space-y-6">
                <header class="space-y-2">
                    <h1 class="hero-title-glow font-display text-3xl font-semibold leading-tight text-base-content sm:text-4xl">
                        Track your games.<br>
                        Know when you'll actually play them.
                    </h1>
                    <p class="max-w-xl text-sm text-base-content/70">
                        Follow upcoming releases, see the latest news for your games, and estimate how long your backlog will take to complete.
                    </p>
                </header>

                <div class="flex flex-wrap items-center gap-3">
                    <a
                        href="{{ url('/register') }}"
                        class="btn btn-primary btn-sm rounded-btn px-5 font-medium"
                    >
                        Start tracking your games
                    </a>
                    <a
                        href="#how-it-works"
                        class="btn btn-ghost btn-sm rounded-btn px-5 font-medium text-base-content/80"
                    >
                        See how it works
                    </a>
                </div>
            </div>

            @if ($this->getHeroPrimary())
                <div class="w-full max-w-xl lg:max-w-none">
                    <x-game.hero-tile :game="$this->getHeroPrimary()" />
                </div>
            @else
                <div class="max-w-xl rounded-box bg-base-300/50 p-6 text-sm text-base-content/70">
                    <p>Start tracking games to see your dashboard with upcoming releases, backlog, and news.</p>
                </div>
            @endif
        </div>
    </section>

    @auth
    {{-- Request a game (authenticated only) --}}
    <section aria-label="Request a game" class="mt-8 sm:mt-10">
        <livewire:game-request-card />
    </section>
    @endauth

    {{-- Upcoming releases --}}
    <section id="features" class="mb-16 sm:mb-20" aria-label="Upcoming releases">
        <x-ui.card-row
            id="upcoming-releases-row"
            title="Upcoming releases"
            subtitle="Track the games you're waiting for and see exactly how long until they release."
        >
            @foreach($this->getUpcomingGames() as $index => $game)
                @php
                    $countdown = $game->release_date ? $game->release_date->diffInDays(now(), false) : null;
                    $countdownText = $countdown !== null ? abs($countdown) . ' days' : null;
                    $newsCount = $game->news_count ?? 0;
                    $statusParts = array_filter([
                        $game->release_date?->format('M j, Y'),
                        $countdownText,
                        $newsCount > 0 ? $newsCount . ' news' : null,
                    ]);
                    $status = implode(' · ', $statusParts) ?: null;
                @endphp
                <x-game.card
                    :game="$game"
                    :status="$status"
                    class="welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }}"
                />
            @endforeach
        </x-ui.card-row>
        <div class="mt-6 px-4">
            <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
                Track your first game
            </a>
        </div>
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
