<?php

use App\Models\Game;
use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public string $sort = 'release_date';

    public string $filter = '';

    public string $platform = '';

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
            ->whereNotNull('release_date')
            ->where('release_date', '>', now())
            ->orderBy('release_date')
            ->limit(4)
            ->get();
    }
};
?>

<div>
    @if ($this->upNext->isNotEmpty())
        <section class="mb-10" aria-label="Up next">
            @php
                $heroGame = $this->upNext->first();
                $nextThree = $this->upNext->skip(1)->take(3);
            @endphp
            @if ($heroGame)
                <a
                    href="{{ route('games.show', $heroGame) }}"
                    class="group relative mb-6 block overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
                    aria-label="{{ $heroGame->title }} — view game"
                >
                    <div class="relative min-h-[14rem] w-full overflow-hidden rounded-xl bg-zinc-900 sm:min-h-[18rem]">
                        @if ($heroGame->cover_image)
                            <img
                                src="{{ $heroGame->cover_image }}"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105"
                            />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-zinc-900/60 to-zinc-950" aria-hidden="true"></div>
                        <div class="absolute inset-0 bg-zinc-900/50 dark:bg-zinc-900/80" aria-hidden="true"></div>
                        <div class="relative flex min-h-[14rem] flex-col justify-end p-6 sm:min-h-[18rem] sm:p-8">
                            <p class="text-xs font-medium uppercase tracking-wider text-cyan-200/90 dark:text-cyan-300/90">Until release</p>
                            @if ($heroGame->release_date && $heroGame->release_date->isFuture())
                                <div
                                    class="mt-1 font-mono text-3xl font-bold tabular-nums text-white sm:text-4xl"
                                    data-countdown
                                    data-release-iso="{{ $heroGame->release_date->toIso8601String() }}"
                                    role="timer"
                                    aria-live="polite"
                                >
                                    <span data-countdown-display>—</span>
                                </div>
                            @endif
                            <h2 class="mt-3 font-display text-xl font-semibold text-white drop-shadow-md sm:text-2xl md:text-3xl">
                                {{ $heroGame->title }}
                            </h2>
                        </div>
                    </div>
                </a>
            @endif
            @if ($nextThree->isNotEmpty())
                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach ($nextThree as $game)
                        <x-game-card :game="$game" />
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
    <section aria-label="All tracked games">
        <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white sm:text-xl">All tracked games</h2>
    <div class="mb-6 mt-4 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="dashboard-sort" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort</label>
            <select id="dashboard-sort" wire:model.live="sort" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="release_date">Release date (newest)</option>
                <option value="recently_added">Recently added</option>
                <option value="alphabetical">Alphabetical</option>
                <option value="countdown">Countdown (soonest first)</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="dashboard-filter" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Filter</label>
            <select id="dashboard-filter" wire:model.live="filter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="">All</option>
                <option value="released">Released</option>
                <option value="upcoming">Upcoming</option>
                <option value="no_date">No release date</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="dashboard-platform" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Platform</label>
            <select id="dashboard-platform" wire:model.live="platform" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="">All platforms</option>
                <option value="PC">PC</option>
                <option value="PlayStation">PlayStation</option>
                <option value="Xbox">Xbox</option>
                <option value="Switch">Switch</option>
            </select>
        </div>
    </div>
    @if ($this->games->isEmpty())
        <p class="text-zinc-600 dark:text-zinc-400">You haven’t tracked any games yet. <a href="{{ url('/') }}" class="underline hover:no-underline">Discover games</a> and tap “Track game” on any title.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($this->games as $game)
                <flux:card class="border-zinc-200/80 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-sm">
                    <a href="{{ route('games.show', $game) }}" class="block focus:outline-none">
                        @if ($game->cover_image)
                            <img src="{{ $game->cover_image }}" alt="" class="aspect-[3/4] w-full rounded-lg object-cover" />
                        @else
                            <div class="aspect-[3/4] flex w-full items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-800">
                                <span class="font-display text-3xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="mt-3">
                            <h2 class="font-display font-semibold text-zinc-900 dark:text-white">{{ $game->title }}</h2>
                            @if ($game->release_date)
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $game->release_date->format('M j, Y') }}</p>
                            @endif
                            @if (count($game->platforms) > 0)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($game->platforms, 0, 3)) }}</p>
                            @endif
                            @if ($game->news->isNotEmpty() && $game->news->first()->published_at)
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Latest news: {{ $game->news->first()->published_at->format('M j') }}</p>
                            @endif
                        </div>
                    </a>
                </flux:card>
            @endforeach
        </div>
    @endif
    </section>
</div>