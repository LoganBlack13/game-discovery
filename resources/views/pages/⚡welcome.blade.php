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
    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

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

    public function getHeroPrimary()
    {
        return $this->getPopularGames()->first();
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
                    class="group cursor-pointer shrink-0"
                    role="button"
                    tabindex="0"
                    data-preview-payload="{{ json_encode($previewPayload) }}"
                    @click.prevent="openPreview(JSON.parse($event.currentTarget.dataset.previewPayload))"
                    @keydown.enter.prevent="openPreview(JSON.parse($event.currentTarget.dataset.previewPayload))"
                >
                    <x-game.card
                        :game="$game"
                        :status="$status"
                        variant="carousel"
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

    {{-- Latest news --}}
    <section aria-label="Stay updated on your games">
        <livewire:welcome-news
            title="Stay updated on your games"
            subtitle="Follow your games and never miss an announcement, trailer, or review."
            :limit="6"
        />
        <div class="mt-6">
            <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-btn">
                Follow your games
            </a>
        </div>
    </section>

    {{-- Features overview --}}
    <section id="how-it-works" class="mt-12 sm:mt-16" aria-label="How it works">
        <x-ui.section-header
            title="Everything you need to stay on top of your games"
            subtitle="One place for upcoming releases, news, and your personal backlog."
        />
        <ul class="grid gap-4 sm:grid-cols-3">
            <li class="card bg-base-300 border border-base-content/10 rounded-box p-6 gap-2">
                <h3 class="font-display font-semibold text-base-content">Upcoming releases</h3>
                <p class="text-sm text-base-content/70">Follow games you're waiting for and get a countdown to their release date.</p>
            </li>
            <li class="card bg-base-300 border border-base-content/10 rounded-box p-6 gap-2">
                <h3 class="font-display font-semibold text-base-content">Latest news</h3>
                <p class="text-sm text-base-content/70">See the latest articles and announcements for every game in your list, all in one feed.</p>
            </li>
            <li class="card bg-base-300 border border-base-content/10 rounded-box p-6 gap-2">
                <h3 class="font-display font-semibold text-base-content">Backlog planning</h3>
                <p class="text-sm text-base-content/70">Track the games you own and plan when you'll realistically get to play the next one.</p>
            </li>
        </ul>
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
