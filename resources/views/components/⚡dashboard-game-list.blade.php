<?php

use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public string $sort = 'release_date';

    public string $filter = '';

    public string $platform = '';

    public string $statusFilter = '';

    public function updateStatus(int $gameId, string $statusValue): void
    {
        $user = auth()->user();
        assert($user instanceof User);
        $user->trackedGames()->updateExistingPivot($gameId, [
            'status' => $statusValue !== '' ? $statusValue : null,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Game>
     */
    public function getGamesProperty(): \Illuminate\Support\Collection
    {
        $query = auth()->user()->trackedGames()
            ->with(['news' => fn ($q) => $q->orderByDesc('published_at')->limit(1)]);

        if ($this->filter === 'released') {
            $query->where(function ($q): void {
                $q->where('release_date', '<=', now())
                    ->orWhere('release_status', \App\Enums\ReleaseStatus::Released);
            });
        } elseif ($this->filter === 'upcoming') {
            $query->where(function ($q): void {
                $q->where('release_date', '>', now())
                    ->orWhereIn('release_status', [\App\Enums\ReleaseStatus::Announced, \App\Enums\ReleaseStatus::ComingSoon]);
            });
        } elseif ($this->filter === 'no_date') {
            $query->whereNull('release_date');
        }

        if ($this->platform !== '') {
            $query->whereJsonContains('platforms', $this->platform);
        }

        if ($this->statusFilter !== '') {
            $query->wherePivot('status', $this->statusFilter);
        }

        return match ($this->sort) {
            'recently_added' => $query->orderByPivot('created_at', 'desc')->get(),
            'alphabetical' => $query->orderBy('title')->get(),
            'countdown' => $query->orderBy('release_date')->get(),
            default => $query->orderByDesc('release_date')->get(),
        };
    }

    /**
     * Up next: up to 4 tracked games with nearest release date first (upcoming only).
     * First = hero, next 3 = row.
     *
     * @return \Illuminate\Support\Collection<int, Game>
     */
    public function getUpNextProperty(): \Illuminate\Support\Collection
    {
        return auth()->user()
            ->trackedGames()
            ->with(['news' => fn ($q) => $q->orderByDesc('published_at')->limit(1)])
            ->whereNotNull('release_date')
            ->where('release_date', '>', now())
            ->orderBy('release_date')
            ->limit(4)
            ->get();
    }

    /**
     * Upcoming releases: up to 6 tracked games with future release date, with news count.
     *
     * @return \Illuminate\Support\Collection<int, Game>
     */
    public function getUpcomingReleasesProperty(): \Illuminate\Support\Collection
    {
        return auth()->user()
            ->trackedGames()
            ->with(['news' => fn ($q) => $q->orderByDesc('published_at')->limit(1)])
            ->whereNotNull('release_date')
            ->where('release_date', '>', now())
            ->withCount('news')
            ->orderBy('release_date')
            ->limit(6)
            ->get();
    }
};
?>

<div
    x-data="{
        previewOpen: false,
        previewGame: null,
        openPreview(gameJson) {
            const g = typeof gameJson === 'string' ? JSON.parse(gameJson) : gameJson;
            if (g.release_date_iso) {
                const r = new Date(g.release_date_iso);
                const n = new Date();
                if (r > n) {
                    const d = Math.floor((r - n) / 86400000);
                    const h = Math.floor(((r - n) % 86400000) / 3600000);
                    const m = Math.floor(((r - n) % 3600000) / 60000);
                    g.countdown_text = d + 'd ' + h + 'h ' + m + 'm';
                } else {
                    g.countdown_text = 'Released';
                }
            } else {
                g.countdown_text = '';
            }
            this.previewGame = g;
            this.previewOpen = true;
        }
    }"
>
    @if ($this->upNext->isNotEmpty())
        <section id="up-next" class="mb-10" aria-label="Up next">
            @php
                $heroGame = $this->upNext->first();
                $nextThree = $this->upNext->skip(1)->take(3);
            @endphp
            @if ($heroGame)
                @php
                    $heroPreview = [
                        'id' => $heroGame->id,
                        'title' => $heroGame->title,
                        'cover_image' => $heroGame->cover_image,
                        'release_date_iso' => $heroGame->release_date?->toIso8601String(),
                        'release_date_formatted' => $heroGame->release_date?->format('M j, Y'),
                        'time_to_beat' => null,
                        'latest_news_title' => $heroGame->news->first()?->title,
                        'game_url' => route('games.show', $heroGame),
                    ];
                @endphp
                <div
                    role="button"
                    tabindex="0"
                    class="group relative mb-6 block cursor-pointer overflow-hidden rounded-box focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
                    aria-label="{{ $heroGame->title }} — open preview"
                    data-game="{{ json_encode($heroPreview) }}"
                    @click="openPreview($event.currentTarget.dataset.game)"
                    @keydown.enter.prevent="openPreview($event.currentTarget.dataset.game)"
                    @keydown.space.prevent="openPreview($event.currentTarget.dataset.game)"
                >
                    <div class="relative min-h-[14rem] w-full overflow-hidden rounded-box bg-base-950 sm:min-h-[18rem]">
                        @if ($heroGame->cover_image)
                            <img
                                src="{{ $heroGame->cover_image }}"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105"
                            />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-base-content/60 to-base-950" aria-hidden="true"></div>
                        <div class="absolute inset-0 bg-base-950/50" aria-hidden="true"></div>
                        <div class="relative flex min-h-[14rem] flex-col justify-end p-6 sm:min-h-[18rem] sm:p-8">
                            <p class="text-xs font-medium uppercase tracking-wider text-primary-content/90">Until release</p>
                            @if ($heroGame->release_date && $heroGame->release_date->isFuture())
                                <div
                                    class="mt-1 font-mono text-3xl font-bold tabular-nums text-base-content sm:text-4xl"
                                    data-countdown
                                    data-release-iso="{{ $heroGame->release_date->toIso8601String() }}"
                                    role="timer"
                                    aria-live="polite"
                                >
                                    <span data-countdown-display>—</span>
                                </div>
                            @endif
                            <h2 class="mt-3 font-display text-xl font-semibold text-base-content drop-shadow-md sm:text-2xl md:text-3xl">
                                {{ $heroGame->title }}
                            </h2>
                        </div>
                    </div>
                </div>
            @endif
            @if ($nextThree->isNotEmpty())
                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach ($nextThree as $game)
                        @php
                            $preview = [
                                'id' => $game->id,
                                'title' => $game->title,
                                'cover_image' => $game->cover_image,
                                'release_date_iso' => $game->release_date?->toIso8601String(),
                                'release_date_formatted' => $game->release_date?->format('M j, Y'),
                                'time_to_beat' => null,
                                'latest_news_title' => $game->news->first()?->title,
                                'game_url' => route('games.show', $game),
                            ];
                        @endphp
                        <div
                            role="button"
                            tabindex="0"
                            class="card compact bg-base-200 border border-base-content/10 cursor-pointer overflow-hidden rounded-box shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-200"
                            aria-label="{{ $game->title }} — open preview"
                            data-game="{{ json_encode($preview) }}"
                            @click="openPreview($event.currentTarget.dataset.game)"
                            @keydown.enter.prevent="openPreview($event.currentTarget.dataset.game)"
                            @keydown.space.prevent="openPreview($event.currentTarget.dataset.game)"
                        >
                            @if ($game->cover_image)
                                <figure class="aspect-[3/4] w-full overflow-hidden">
                                    <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                                </figure>
                            @else
                                <div class="aspect-[3/4] flex w-full items-center justify-center bg-base-300">
                                    <span class="font-display text-3xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="card-body gap-0 p-3">
                                <h3 class="card-title font-display text-base font-semibold text-base-content">{{ $game->title }}</h3>
                                @if ($game->release_date)
                                    <p class="mt-0.5 text-sm text-base-content/70">{{ $game->release_date->format('M j, Y') }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <script>
                (function () {
                    const containers = document.querySelectorAll('[data-countdown]');
                    containers.forEach(function (el) {
                        const releaseIso = el.dataset.releaseIso;
                        const display = el.querySelector('[data-countdown-display]');
                        if (!releaseIso || !display) return;
                        function update() {
                            const now = new Date();
                            const release = new Date(releaseIso);
                            if (release <= now) {
                                display.textContent = 'Released';
                                return;
                            }
                            const d = Math.max(0, Math.floor((release - now) / 86400000));
                            const h = Math.max(0, Math.floor(((release - now) % 86400000) / 3600000));
                            const m = Math.max(0, Math.floor(((release - now) % 3600000) / 60000));
                            display.textContent = d + 'd ' + h + 'h ' + m + 'm';
                        }
                        update();
                        setInterval(update, 60000);
                    });
                })();
            </script>
        </section>
    @endif

    <section id="upcoming" aria-label="Upcoming releases" class="mb-10">
        <h2 class="font-display text-lg font-semibold text-base-content sm:text-xl">Upcoming releases</h2>
        <p class="mt-1 text-sm text-base-content/70">Track the games you're waiting for.</p>
        @if ($this->upcomingReleases->isNotEmpty())
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                @foreach ($this->upcomingReleases as $game)
                    @php
                        $preview = [
                            'id' => $game->id,
                            'title' => $game->title,
                            'cover_image' => $game->cover_image,
                            'release_date_iso' => $game->release_date?->toIso8601String(),
                            'release_date_formatted' => $game->release_date?->format('M j, Y'),
                            'time_to_beat' => null,
                            'latest_news_title' => $game->news->first()?->title,
                            'game_url' => route('games.show', $game),
                        ];
                    @endphp
                    <div
                        role="button"
                        tabindex="0"
                        class="card compact bg-base-200 border border-base-content/10 cursor-pointer overflow-hidden rounded-box shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-200"
                        aria-label="{{ $game->title }} — open preview"
                        data-game="{{ json_encode($preview) }}"
                        @click="openPreview($event.currentTarget.dataset.game)"
                        @keydown.enter.prevent="openPreview($event.currentTarget.dataset.game)"
                        @keydown.space.prevent="openPreview($event.currentTarget.dataset.game)"
                    >
                        @if ($game->cover_image)
                            <figure class="aspect-[3/4] w-full overflow-hidden">
                                <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                            </figure>
                        @else
                            <div class="aspect-[3/4] flex w-full items-center justify-center bg-base-300">
                                <span class="font-display text-3xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="card-body gap-0 p-3">
                            <h3 class="card-title font-display text-base font-semibold text-base-content">{{ $game->title }}</h3>
                            @if ($game->release_date && $game->release_date->isFuture())
                                <p class="mt-1 text-xs text-base-content/70">
                                    <span
                                        data-countdown
                                        data-release-iso="{{ $game->release_date->toIso8601String() }}"
                                        role="timer"
                                        aria-live="polite"
                                    >
                                        <span data-countdown-display>—</span>
                                    </span>
                                    · {{ $game->release_date->format('M j, Y') }}
                                </p>
                            @endif
                            @if ($game->news_count > 0)
                                <p class="mt-0.5 text-xs text-base-content/60">{{ $game->news_count }} {{ $game->news_count === 1 ? 'news' : 'news' }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4">
                <a href="{{ route('games.index') }}" class="btn btn-primary btn-sm">Browse more games</a>
            </p>
        @else
            <p class="mt-4 text-sm text-base-content/70">No upcoming releases in your list. <a href="{{ route('games.index') }}" class="link link-primary">Discover games</a> and track ones you're waiting for.</p>
        @endif
    </section>

    <section id="backlog" aria-label="Plan your gaming backlog" class="mb-10">
        {{-- Task 4: Backlog planning with example data and total hours --}}
    </section>

    <section id="playable-insight" aria-label="When will you actually play it?" class="mb-10">
        {{-- Task 5: Playable date insight table/cards --}}
    </section>

    <section id="all-tracked" aria-label="All tracked games">
        <h2 class="font-display text-lg font-semibold text-base-content sm:text-xl">All tracked games</h2>
    <div class="mb-6 mt-4 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="dashboard-sort" class="text-sm font-medium text-base-content">Sort</label>
            <select id="dashboard-sort" wire:model.live="sort" class="select select-bordered select-sm">
                <option value="release_date">Release date (newest)</option>
                <option value="recently_added">Recently added</option>
                <option value="alphabetical">Alphabetical</option>
                <option value="countdown">Countdown (soonest first)</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="dashboard-filter" class="text-sm font-medium text-base-content">Filter</label>
            <select id="dashboard-filter" wire:model.live="filter" class="select select-bordered select-sm">
                <option value="">All</option>
                <option value="released">Released</option>
                <option value="upcoming">Upcoming</option>
                <option value="no_date">No release date</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="dashboard-platform" class="text-sm font-medium text-base-content">Platform</label>
            <select id="dashboard-platform" wire:model.live="platform" class="select select-bordered select-sm">
                <option value="">All platforms</option>
                <option value="PC">PC</option>
                <option value="PlayStation">PlayStation</option>
                <option value="Xbox">Xbox</option>
                <option value="Switch">Switch</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="dashboard-status" class="text-sm font-medium text-base-content">Status</label>
            <select id="dashboard-status" wire:model.live="statusFilter" class="select select-bordered select-sm">
                <option value="">All</option>
                @foreach (\App\Enums\TrackedGameStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @if ($this->games->isEmpty())
        <p class="text-base-content/70">You haven’t tracked any games yet. <a href="{{ url('/') }}" class="underline hover:no-underline">Discover games</a> and tap “Track game” on any title.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($this->games as $game)
                @php
                    $preview = [
                        'id' => $game->id,
                        'title' => $game->title,
                        'cover_image' => $game->cover_image,
                        'release_date_iso' => $game->release_date?->toIso8601String(),
                        'release_date_formatted' => $game->release_date?->format('M j, Y'),
                        'time_to_beat' => null,
                        'latest_news_title' => $game->news->first()?->title,
                        'game_url' => route('games.show', $game),
                    ];
                @endphp
                <div
                    role="button"
                    tabindex="0"
                    class="card compact bg-base-200 border border-base-content/10 cursor-pointer shadow-sm overflow-hidden rounded-box transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-200"
                    aria-label="{{ $game->title }} — open preview"
                    data-game="{{ json_encode($preview) }}"
                    @click="openPreview($event.currentTarget.dataset.game)"
                    @keydown.enter.prevent="openPreview($event.currentTarget.dataset.game)"
                    @keydown.space.prevent="openPreview($event.currentTarget.dataset.game)"
                >
                    @if ($game->cover_image)
                        <figure class="aspect-[3/4] w-full overflow-hidden">
                            <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
                        </figure>
                    @else
                        <div class="aspect-[3/4] flex w-full items-center justify-center bg-base-300">
                            <span class="font-display text-3xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="card-body gap-0 p-3">
                        <h2 class="card-title font-display text-base font-semibold text-base-content">{{ $game->title }}</h2>
                        @if ($game->release_date)
                            <p class="mt-0.5 text-sm text-base-content/70">{{ $game->release_date->format('M j, Y') }}</p>
                        @endif
                        @if (count($game->platforms) > 0)
                            <p class="mt-0.5 text-xs text-base-content/60">{{ implode(', ', array_slice($game->platforms, 0, 3)) }}</p>
                        @endif
                        @if ($game->news->isNotEmpty() && $game->news->first()->published_at)
                            <p class="mt-1 text-xs text-base-content/60">Latest news: {{ $game->news->first()->published_at->format('M j') }}</p>
                        @endif
                        <div class="mt-2" @click.stop @keydown.stop>
                            <select
                                wire:change="updateStatus({{ $game->id }}, $event.target.value)"
                                class="select select-bordered select-xs w-full"
                                aria-label="Status for {{ $game->title }}"
                            >
                                <option value="">No status</option>
                                @foreach (\App\Enums\TrackedGameStatus::cases() as $status)
                                    <option
                                        value="{{ $status->value }}"
                                        @selected($game->pivot?->status === $status->value)
                                    >{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </section>

    <x-dashboard.game-preview-panel />
</div>