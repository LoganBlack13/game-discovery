@props([
    'game',
    'status' => null,
])

<?php
    $statusText = $status ?? ($game->release_date?->format('M j, Y'));
    $platforms = is_array($game->platforms ?? null) ? $game->platforms : [];
?>

<article {{ $attributes->class('relative overflow-hidden rounded-box bg-base-300 text-base-content shadow-2xl ring-1 ring-base-content/10') }}>
    <div class="relative grid gap-0 lg:grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)]">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-t from-base-100/90 via-base-100/40 to-transparent"></div>
            @if ($game->cover_image)
                <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
            @else
                <div class="flex h-full min-h-[220px] items-center justify-center bg-base-200">
                    <span class="font-display text-6xl font-extrabold text-base-content/30">
                        {{ mb_substr($game->title, 0, 1) }}
                    </span>
                </div>
            @endif
            <div class="hero-tile-image-glow pointer-events-none absolute inset-0"></div>
        </div>
        <div class="relative flex flex-col justify-between gap-4 p-6 lg:p-7">
            <header class="space-y-2">
                <h3 class="hero-title-glow font-display text-2xl font-semibold leading-tight sm:text-3xl">
                    {{ $game->title }}
                </h3>
                @if ($statusText || count($platforms) > 0)
                    <p class="text-xs text-base-content/70">
                        @if ($statusText)
                            <span>{{ $statusText }}</span>
                        @endif
                        @if ($statusText && count($platforms) > 0)
                            <span class="mx-1 text-base-content/40">•</span>
                        @endif
                        @if (count($platforms) > 0)
                            <span>{{ implode(', ', array_slice($platforms, 0, 3)) }}</span>
                        @endif
                    </p>
                @endif
            </header>

            <div class="mt-2">
                <a
                    href="{{ route('games.show', $game) }}"
                    class="btn btn-primary btn-sm rounded-btn px-5 font-medium"
                >
                    View game
                </a>
            </div>
        </div>
    </div>
</article>

