@props([
    'game',
    'status' => null,
    'tags' => [],
    'variant' => 'default',
])

<?php
    $statusText = $status ?? ($game->release_date?->format('M j, Y'));
    $platforms = is_array($game->platforms ?? null) ? $game->platforms : [];

    $baseClasses = 'card bg-base-300 border border-base-300 text-base-content transition-all duration-200 hover:-translate-y-0.5 hover:border-primary hover:shadow-primary/20 hover:ring-2 hover:ring-primary/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100';
    $variants = [
        'default' => 'w-[280px] min-w-[280px] max-w-[280px] shadow-xl shrink-0',
        'compact' => 'w-64 min-w-[16rem] shadow-md shrink-0',
        'list' => 'w-full shadow-sm',
    ];

    $variantClasses = $variants[$variant] ?? $variants['default'];
?>

<a
    href="{{ route('games.show', $game) }}"
    {{ $attributes->class("welcome-game-card {$baseClasses} {$variantClasses}") }}
    aria-label="{{ $game->title }} — view game"
>
    <figure class="aspect-[3/4] w-full overflow-hidden rounded-t-2xl">
        @if ($game->cover_image)
            <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" />
        @else
            <div class="flex size-full items-center justify-center bg-base-200">
                <span class="font-display text-3xl font-bold text-base-content/40">
                    {{ mb_substr($game->title, 0, 1) }}
                </span>
            </div>
        @endif
    </figure>
    <div class="card-body gap-1 rounded-b-2xl p-3">
        <h3 class="card-title font-display text-base font-semibold text-base-content">
            {{ $game->title }}
        </h3>

        @if ($statusText)
            <p class="text-xs text-base-content/70">
                {{ $statusText }}
            </p>
        @endif

        @if (count($platforms) > 0)
            <p class="text-[11px] text-base-content/60">
                {{ implode(', ', array_slice($platforms, 0, 3)) }}
            </p>
        @endif

        @if (! empty($tags))
            <div class="mt-1 flex flex-wrap gap-1">
                @foreach ($tags as $tag)
                    <span class="badge badge-ghost badge-xs text-[10px] font-medium">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        @if (trim($slot) !== '')
            <div class="mt-2 flex items-center justify-between gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</a>

