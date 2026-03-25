<?php

use App\Models\Game;
use App\Services\UserGameSearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    use AuthorizesRequests;

    public string $query = '';

    /**
     * @return Collection<int, \App\Services\UserGameSearchResult>
     */
    public function getResultsProperty(): Collection
    {
        return app(UserGameSearchService::class)->search(
            auth()->user(),
            $this->query,
            10
        );
    }

    public function trackGame(int $gameId): void
    {
        $game = Game::query()->findOrFail($gameId);
        $this->authorize('track', $game);
        auth()->user()->trackedGames()->syncWithoutDetaching([$game->id]);
    }

    public function untrackGame(int $gameId): void
    {
        $game = Game::query()->findOrFail($gameId);
        $this->authorize('untrack', $game);
        auth()->user()->trackedGames()->detach($game->id);
    }
};
?>

<div
    class="fixed inset-0 z-10 flex items-center justify-center p-4"
    role="search"
    aria-label="Search games"
    x-data="{ highlightedIndex: -1 }"
    x-ref="spotlightRoot"
    x-on:spotlight-opened.window="$refs.spotlightPanel?.querySelector('input')?.focus()"
    x-on:keydown.window="
        if (!$el.closest('.spotlight-open')) return;
        const target = $event.target;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) return;
        if ($event.key !== 'ArrowDown' && $event.key !== 'ArrowUp' && $event.key !== 'Enter') return;
        const links = $el.querySelectorAll('[data-game-url]');
        const count = links.length;
        if ($event.key === 'ArrowDown') { $event.preventDefault(); highlightedIndex = Math.min(highlightedIndex + 1, count - 1); links[highlightedIndex]?.scrollIntoView({ block: 'nearest' }); }
        if ($event.key === 'ArrowUp') { $event.preventDefault(); highlightedIndex = Math.max(0, highlightedIndex - 1); links[highlightedIndex]?.scrollIntoView({ block: 'nearest' }); }
        if ($event.key === 'Enter' && highlightedIndex >= 0 && links[highlightedIndex]) { $event.preventDefault(); window.location = links[highlightedIndex].getAttribute('data-game-url'); }
    "
>
    <div
        class="spotlight-backdrop absolute inset-0 z-0 bg-black/50 dark:bg-black/70"
        aria-hidden="true"
        @click="$dispatch('close-game-search')"
    ></div>
    <div
        x-ref="spotlightPanel"
        class="spotlight-panel relative z-10 flex w-full max-w-[calc(100%-2rem)] max-h-[85vh] flex-col overflow-hidden rounded-box bg-base-100 shadow-2xl ring-1 ring-base-300 lg:min-w-[50vw] lg:max-w-2xl"
        @click.stop
    >
        <div class="flex shrink-0 flex-col px-4 pt-4 pb-3">
            <label class="sr-only" for="game-search-input">Search games</label>
            <input
                id="game-search-input"
                type="search"
                wire:model.live.debounce.300ms="query"
                placeholder="Search games…"
                class="spotlight-input input input-bordered w-full rounded-box border-base-300 bg-base-200/50 py-3.5 text-base placeholder:text-base-content/50 focus:bg-base-100 sm:text-lg"
                aria-label="Search games"
            />
        </div>
        <div class="min-h-0 flex-1 overflow-hidden">
            @if(trim($query) !== '')
                <div wire:loading class="px-4 py-2 text-sm text-base-content/60">Searching…</div>
            @endif
            <div class="max-h-[60vh] overflow-y-auto py-2 pr-3 pl-4 scrollbar-hidden">
                <ul class="flex flex-col gap-1.5" role="listbox" aria-label="Search results">
                    @foreach($this->results as $result)
                        @php
                            $game = $result->game;
                        @endphp
                        <li
                            class="search-result-item flex items-center gap-3 rounded-box border border-base-300/80 px-3 py-2.5 shadow-sm transition-all duration-150 ease-out bg-base-100 hover:border-base-300"
                            style="animation-delay: {{ $loop->index * 0.02 }}s"
                            data-game-url="{{ route('games.show', $game) }}"
                            role="option"
                            :aria-selected="highlightedIndex === {{ $loop->index }}"
                            :class="highlightedIndex === {{ $loop->index }} ? 'border-primary ring-2 ring-primary/30 bg-primary/10' : ''"
                        >
                            <a
                                href="{{ route('games.show', $game) }}"
                                class="flex min-w-0 flex-1 items-center gap-3 focus:outline-none"
                            >
                                @if ($game->cover_image)
                                    <img src="{{ $game->cover_image }}" alt="" class="h-16 w-12 shrink-0 rounded-box object-cover ring-1 ring-base-300 shadow-sm" />
                                @else
                                    <div class="flex h-16 w-12 shrink-0 items-center justify-center rounded-box bg-base-300 text-sm font-semibold text-base-content/70">{{ substr($game->title, 0, 1) }}</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-base-content">{{ $game->title }}</span>
                                    <div class="text-sm text-base-content/70">
                                        @if ($game->release_date)
                                            <span>{{ $game->release_date->format('M j, Y') }}</span>
                                        @endif
                                        @if (count($game->platforms) > 0)
                                            @if ($game->release_date)
                                                <span> · </span>
                                            @endif
                                            <span>{{ implode(', ', array_slice($game->platforms, 0, 3)) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                            <div class="shrink-0" x-on:click.stop wire:key="track-{{ $game->id }}">
                                @guest
                                    <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:underline" x-on:click.stop>Log in to track</a>
                                @else
                                    @if ($result->isTracked)
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-sm"
                                            wire:click="untrackGame({{ $game->id }})"
                                            wire:loading.attr="disabled"
                                            aria-label="Remove {{ $game->title }} from tracking"
                                        >
                                            <span wire:loading.remove wire:target="untrackGame">Remove from tracking</span>
                                            <span wire:loading wire:target="untrackGame">…</span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm"
                                            wire:click="trackGame({{ $game->id }})"
                                            wire:loading.attr="disabled"
                                            aria-label="Track {{ $game->title }}"
                                        >
                                            <span wire:loading.remove wire:target="trackGame">Track game</span>
                                            <span wire:loading wire:target="trackGame">…</span>
                                        </button>
                                    @endif
                                @endguest
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @if (trim($query) === '')
            <p class="px-4 py-5 text-center text-sm text-base-content/60">Type to search games</p>
        @elseif($this->results->isEmpty())
            <p class="px-4 py-5 text-center text-sm text-base-content/60">No games found.</p>
        @endif
    </div>
</div>
