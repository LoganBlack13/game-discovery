<x-layouts.app :title="$game->title">
    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-8 sm:flex-row sm:gap-12">
            @if ($game->cover_image)
                <div class="shrink-0">
                    <img
                        src="{{ $game->cover_image }}"
                        alt=""
                        class="aspect-[3/4] w-full max-w-sm rounded-xl object-cover shadow-lg sm:w-64"
                    />
                </div>
            @else
                <div class="flex aspect-[3/4] w-full max-w-sm shrink-0 items-center justify-center rounded-xl bg-zinc-200 dark:bg-zinc-800 sm:w-64">
                    <span class="font-display text-4xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                </div>
            @endif
            <div class="min-w-0 flex-1 space-y-4">
                <h1 class="font-display text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">{{ $game->title }}</h1>
                @if ($game->developer)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-700 dark:text-zinc-300">Developer</span> {{ $game->developer }}</p>
                @endif
                @if ($game->publisher)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-700 dark:text-zinc-300">Publisher</span> {{ $game->publisher }}</p>
                @endif
                @if ($game->release_date)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-700 dark:text-zinc-300">Release date</span> {{ $game->release_date->format('F j, Y') }}</p>
                @endif
                @if ($game->release_date && $game->release_date->isFuture())
                    <div
                        class="rounded-xl bg-cyan-50 p-4 dark:bg-cyan-950/30"
                        data-release-iso="{{ $game->release_date->toIso8601String() }}"
                        data-countdown
                        role="timer"
                        aria-live="polite"
                    >
                        <p class="text-sm font-medium text-cyan-800 dark:text-cyan-200">Time until release</p>
                        <p class="mt-1 font-mono text-lg tabular-nums text-cyan-900 dark:text-cyan-100" data-countdown-display>—</p>
                    </div>
                @elseif ($game->release_date && $game->release_date->isPast())
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Released</p>
                @endif
                <p class="text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-700 dark:text-zinc-300">Status</span> {{ $game->release_status->value }}</p>
                @if (count($game->genres) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($game->genres as $genre)
                            <span class="rounded-full bg-zinc-200 px-3 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif
                @if (count($game->platforms) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($game->platforms as $platform)
                            <span class="rounded-full bg-cyan-100 px-3 py-0.5 text-xs font-medium text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300">{{ $platform }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="pt-2">
                    @guest
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950" aria-label="Log in to track this game">
                            Log in to track
                        </a>
                    @else
                        @if ($isTracked)
                            <form action="{{ route('games.untrack', $game) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700" aria-label="Remove {{ $game->title }} from tracking">
                                    Remove from tracking
                                </button>
                            </form>
                        @else
                            <form action="{{ route('games.track', $game) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950" aria-label="Track {{ $game->title }}">
                                    Track game
                                </button>
                            </form>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
        @if ($game->description)
            <div class="mt-10 border-t border-zinc-200 pt-10 dark:border-zinc-800">
                <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white">About</h2>
                <p class="mt-3 whitespace-pre-wrap text-zinc-600 dark:text-zinc-400">{{ $game->description }}</p>
            </div>
        @endif

        @if ($game->news->isNotEmpty())
            <section class="mt-10 border-t border-zinc-200 pt-10 dark:border-zinc-800" aria-label="News">
                <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white">News</h2>
                <ul class="mt-4 flex flex-col gap-4">
                    @foreach ($game->news as $item)
                        <li>
                            <a
                                href="{{ $item->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex gap-4 rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900/50"
                            >
                                @if ($item->thumbnail)
                                    <img src="{{ $item->thumbnail }}" alt="" class="h-20 w-32 shrink-0 rounded object-cover" />
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $item->title }}</span>
                                    @if ($item->source)
                                        <span class="ml-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->source }}</span>
                                    @endif
                                    @if ($item->published_at)
                                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->published_at->format('M j, Y') }}</p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($game->release_date && $game->release_date->isFuture())
            <script>
                (function () {
                    const el = document.querySelector('[data-countdown]');
                    const display = document.querySelector('[data-countdown-display]');
                    if (!el || !display) return;
                    const releaseIso = el.dataset.releaseIso;
                    if (!releaseIso) return;
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
                })();
            </script>
        @endif
    </article>
</x-layouts.app>
