@props([
    'game',
    'statusLabel' => null,
])

@php
    $status = $statusLabel ?? ($game->release_date?->format('M j, Y'));
@endphp
<a
    href="{{ route('games.show', $game) }}"
    class="welcome-game-card card compact shrink-0 w-[280px] min-w-[280px] max-w-[280px] bg-base-300 border border-base-300 shadow-xl transition-all duration-200 hover:-translate-y-0.5 hover:border-primary hover:shadow-primary/20 hover:ring-2 hover:ring-primary/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
    aria-label="{{ $game->title }} — view game"
>
    <figure class="aspect-[3/4] w-full overflow-hidden rounded-t-2xl">
        @if ($game->cover_image)
            <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
        @else
            <div class="flex size-full items-center justify-center bg-base-200">
                <span class="font-display text-3xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
            </div>
        @endif
    </figure>
    <div class="card-body gap-0 rounded-b-2xl p-3">
        <h3 class="card-title font-display text-base font-semibold text-base-content">{{ $game->title }}</h3>
        @if ($status)
            <p class="mt-0.5 text-sm text-base-content/70">{{ $status }}</p>
        @endif
        @if (count($game->platforms) > 0)
            <p class="mt-0.5 text-xs text-base-content/60">{{ implode(', ', array_slice($game->platforms, 0, 2)) }}</p>
        @endif
    </div>
</a>
