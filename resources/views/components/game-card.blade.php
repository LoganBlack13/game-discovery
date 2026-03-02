@props([
    'game',
])

@php
    $game = $game ?? null;
@endphp
@if ($game)
    <a
        href="{{ route('games.show', $game) }}"
        class="group relative block overflow-hidden rounded-box bg-base-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
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
                <div class="flex h-full w-full items-center justify-center bg-base-300">
                    <span class="font-display text-4xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
                </div>
            @endif
            <div
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-base-content/90 via-base-content/50 to-transparent pt-16"
                aria-hidden="true"
            ></div>
            <div class="absolute inset-x-0 bottom-0 p-4">
                <h2 class="font-display text-lg font-semibold text-base-content drop-shadow-md sm:text-xl">
                    {{ $game->title }}
                </h2>
                @if ($game->release_date && $game->release_date->isFuture())
                    <div
                        class="mt-2 rounded-box bg-primary/20 px-3 py-1.5 backdrop-blur-sm"
                        data-countdown
                        data-release-iso="{{ $game->release_date->toIso8601String() }}"
                        role="timer"
                        aria-live="polite"
                    >
                        <p class="text-xs font-medium uppercase tracking-wider text-primary-content">Until release</p>
                        <p class="font-mono text-sm font-semibold tabular-nums text-primary-content/90" data-countdown-display>—</p>
                    </div>
                @elseif ($game->release_date && $game->release_date->isPast())
                    <p class="mt-1 text-xs font-medium text-base-content/70">Released</p>
                @endif
            </div>
        </div>
    </a>
@endif
