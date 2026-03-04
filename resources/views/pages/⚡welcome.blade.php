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
            ->with(['news' => fn ($q) => $q->orderByDesc('published_at')->limit(1)])
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

    /**
     * @return \Illuminate\Support\Collection<int, array{title: string, cover_image: string|null, hours: int}>
     */
    public function getSampleBacklogItems()
    {
        $items = [
            ['title' => 'Elden Ring', 'slug' => 'elden-ring', 'hours' => 60],
            ['title' => 'Baldur\'s Gate 3', 'slug' => 'baldurs-gate-3', 'hours' => 80],
            ['title' => 'Cyberpunk 2077', 'slug' => 'cyberpunk-2077', 'hours' => 25],
        ];
        $games = Game::query()
            ->whereIn('slug', array_column($items, 'slug'))
            ->get()
            ->keyBy('slug');

        return collect($items)->map(function (array $row) use ($games): array {
            $game = $games->get($row['slug']);

            return [
                'title' => $row['title'],
                'cover_image' => $game?->cover_image,
                'hours' => $row['hours'],
            ];
        });
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
    <section
        id="features"
        class="mb-16 sm:mb-20"
        aria-label="Upcoming releases"
        x-data="{
            previewOpen: false,
            previewGame: null,
            openPreview(payload) {
                this.previewGame = payload;
                this.previewOpen = true;
            },
        }"
    >
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
                    $latestNews = $game->news->first();
                    $previewPayload = [
                        'title' => $game->title,
                        'gameUrl' => route('games.show', $game),
                        'releaseDate' => $game->release_date?->format('M j, Y'),
                        'countdown' => $countdownText,
                        'latestNewsTitle' => $latestNews?->title,
                    ];
                @endphp
                <div
                    class="cursor-pointer shrink-0"
                    role="button"
                    tabindex="0"
                    data-preview-payload="{{ json_encode($previewPayload) }}"
                    @click.prevent="openPreview(JSON.parse($event.currentTarget.dataset.previewPayload))"
                    @keydown.enter.prevent="openPreview(JSON.parse($event.currentTarget.dataset.previewPayload))"
                >
                    <x-game.card
                        :game="$game"
                        :status="$status"
                        class="welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }} pointer-events-none"
                    />
                </div>
            @endforeach
        </x-ui.card-row>
        <div class="mt-6 px-4">
            <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
                Track your first game
            </a>
        </div>
        <x-home.game-preview-panel />
    </section>

    {{-- Plan your gaming backlog (sample) --}}
    <section class="mb-16 sm:mb-20" aria-label="Plan your gaming backlog">
        <x-ui.section-header
            title="Plan your gaming backlog"
            subtitle="See how long your games take to finish and estimate the total time needed to complete your backlog."
        />
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
            @foreach ($this->getSampleBacklogItems() as $item)
                <li class="card bg-base-300 border border-base-content/10 rounded-box overflow-hidden flex flex-row gap-4">
                    <figure class="aspect-[3/4] w-24 shrink-0 overflow-hidden">
                        @if ($item['cover_image'])
                            <img src="{{ $item['cover_image'] }}" alt="" class="size-full object-cover" />
                        @else
                            <div class="flex size-full items-center justify-center bg-base-200">
                                <span class="font-display text-2xl font-bold text-base-content/40">{{ mb_substr($item['title'], 0, 1) }}</span>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body justify-center p-4 gap-0">
                        <h3 class="font-display font-semibold text-base-content">{{ $item['title'] }}</h3>
                        <p class="text-sm text-base-content/70">~{{ $item['hours'] }} hours</p>
                    </div>
                </li>
            @endforeach
        </ul>
        @php $totalHours = $this->getSampleBacklogItems()->sum('hours'); @endphp
        <p class="text-base font-semibold text-base-content mb-4">
            Total backlog time
            <span class="block text-2xl text-primary">{{ $totalHours }} hours</span>
        </p>
        <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
            Plan your backlog
        </a>
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

    {{-- Stay updated on your games --}}
    <section id="latest-news" class="mt-12 sm:mt-16" aria-label="Stay updated on your games">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)]">
            <div>
                <livewire:welcome-news
                    :limit="5"
                    title="Stay updated on your games"
                    subtitle="Automatically receive the latest news for the games you track."
                />
                <div class="mt-6">
                    <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
                        Follow your games
                    </a>
                </div>
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

    {{-- When will you actually play it? --}}
    <section id="how-it-works" class="mt-12 sm:mt-16" aria-label="When will you actually play it?">
        <x-ui.section-header
            title="When will you actually play it?"
            subtitle="Your backlog determines when you'll start new games."
        />
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Release date</th>
                        <th>Backlog remaining</th>
                        <th>Estimated playable date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Silksong</td>
                        <td>Release in 120 days</td>
                        <td>Backlog: 90 hours</td>
                        <td>Playable in about 2 weeks</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
                Calculate your backlog
            </a>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="mt-12 sm:mt-16 py-12 sm:py-16 text-center" aria-label="Get started">
        <p class="text-lg font-medium text-base-content mb-6">
            Track your games and plan your backlog.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="{{ url('/register') }}" class="btn btn-primary rounded-btn px-6">
                Start tracking your games
            </a>
            <a href="{{ url('/login') }}" class="link link-hover font-medium text-base-content/80">
                Sign in
            </a>
        </div>
    </section>
</div>
