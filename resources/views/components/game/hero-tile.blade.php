@props([
    'game',
    'status' => null,
    'reasons' => [],
])

<?php
    $statusText = $status ?? ($game->release_date?->format('M j, Y'));
    $platforms = is_array($game->platforms ?? null) ? $game->platforms : [];
?>

<article {{ $attributes->class('relative overflow-hidden rounded-3xl bg-base-300 text-base-content shadow-2xl ring-1 ring-base-content/10') }}>
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
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.2),transparent_55%)]"></div>
        </div>
        <div class="relative flex flex-col justify-between gap-4 p-6 lg:p-7">
            <header class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary/80">
                    Tonight’s pick
                </p>
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

            @if (! empty($reasons))
                <div class="flex flex-wrap gap-2">
                    @foreach ($reasons as $reason)
                        <span class="badge badge-outline badge-sm border-primary/40 bg-primary/5 text-[11px] font-medium text-primary">
                            {{ $reason }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('games.show', $game) }}"
                    class="btn btn-primary btn-sm rounded-full px-5 font-medium"
                >
                    Play now
                </a>
                <button type="button" class="btn btn-ghost btn-sm rounded-full px-4 text-sm">
                    View details
                </button>
            </div>
        </div>
    </div>
</article>

