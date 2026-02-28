@props([
    'game',
])

@php
    $game = $game ?? null;
@endphp
@if ($game)
    <a
        href="{{ route('games.show', $game) }}"
        class="group relative block overflow-hidden rounded-xl bg-zinc-200 dark:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
        aria-label="{{ $game->title }} — view game"
    >
        <div class="relative aspect-[3/4] w-full overflow-hidden">
            @if ($game->cover_image)
                <img
                    src="{{ $game->cover_image }}"
                    alt=""
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                />
            @else
                <div class="flex h-full w-full items-center justify-center bg-zinc-300 dark:bg-zinc-700">
                    <span class="font-display text-4xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game->title, 0, 1) }}</span>
                </div>
            @endif
            <div
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-zinc-900/90 via-zinc-900/50 to-transparent pt-16 dark:from-zinc-950/95 dark:via-zinc-950/60"
                aria-hidden="true"
            ></div>
            <div class="absolute inset-x-0 bottom-0 p-4">
                <h2 class="font-display text-lg font-semibold text-white drop-shadow-md sm:text-xl">
                    {{ $game->title }}
                </h2>
                @if ($game->release_date && $game->release_date->isFuture())
                    <div
                        class="mt-2 rounded-lg bg-cyan-500/20 px-3 py-1.5 backdrop-blur-sm dark:bg-cyan-400/15"
                        data-countdown
                        data-release-iso="{{ $game->release_date->toIso8601String() }}"
                        role="timer"
                        aria-live="polite"
                    >
                        <p class="text-xs font-medium uppercase tracking-wider text-cyan-200 dark:text-cyan-300">Until release</p>
                        <p class="font-mono text-sm font-semibold tabular-nums text-cyan-100 dark:text-cyan-200" data-countdown-display>—</p>
                    </div>
                @elseif ($game->release_date && $game->release_date->isPast())
                    <p class="mt-1 text-xs font-medium text-zinc-300 dark:text-zinc-400">Released</p>
                @endif
            </div>
        </div>
    </a>
@endif
