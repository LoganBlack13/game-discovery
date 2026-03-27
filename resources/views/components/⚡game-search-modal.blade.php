<?php

use App\Models\Game;
use App\Services\UserGameSearchResult;
use App\Services\UserGameSearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use AuthorizesRequests;

    public string $query = '';

    #[On('open-game-search')]
    public function resetQuery(): void
    {
        $this->query = '';
    }

    /**
     * @return Collection<int, UserGameSearchResult>
     */
    #[Computed]
    public function results(): Collection
    {
        return app(UserGameSearchService::class)->search(
            auth()->user(),
            $this->query,
            8
        );
    }

    public function trackGame(string $gameId): void
    {
        $game = Game::query()->findOrFail($gameId);
        $this->authorize('track', $game);
        auth()->user()->trackedGames()->syncWithoutDetaching([$game->id]);
    }

    public function untrackGame(string $gameId): void
    {
        $game = Game::query()->findOrFail($gameId);
        $this->authorize('untrack', $game);
        auth()->user()->trackedGames()->detach($game->id);
    }
};
?>

<div
    class="fixed inset-0 z-10 flex items-start justify-center px-4 pt-[15vh]"
    role="search"
    aria-label="Search games"
    x-data="{ highlightedIndex: -1 }"
    x-ref="spotlightRoot"
    x-on:spotlight-opened.window="$refs.spotlightPanel?.querySelector('input')?.focus(); highlightedIndex = -1"
    x-on:keydown.window="
        if (!$el.closest('.spotlight-open')) return;
        const target = $event.target;
        if (target && target.tagName === 'TEXTAREA') return;
        if ($event.key !== 'ArrowDown' && $event.key !== 'ArrowUp' && $event.key !== 'Enter') return;
        const links = $el.querySelectorAll('[data-game-url]');
        const count = links.length;
        if ($event.key === 'ArrowDown') { $event.preventDefault(); highlightedIndex = Math.min(highlightedIndex + 1, count - 1); links[highlightedIndex]?.scrollIntoView({ block: 'nearest' }); }
        if ($event.key === 'ArrowUp') { $event.preventDefault(); highlightedIndex = Math.max(0, highlightedIndex - 1); links[highlightedIndex]?.scrollIntoView({ block: 'nearest' }); }
        if ($event.key === 'Enter' && highlightedIndex >= 0 && links[highlightedIndex]) { $event.preventDefault(); window.location = links[highlightedIndex].getAttribute('data-game-url'); }
    "
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        aria-hidden="true"
        @click="$dispatch('close-game-search')"
    ></div>

    {{-- Panel --}}
    <div
        x-ref="spotlightPanel"
        class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-base-100 shadow-2xl ring-1 ring-base-content/10"
        @click.stop
    >
        {{-- Search input --}}
        <div class="flex items-center gap-3 border-b border-base-content/10 px-4">
            <svg class="size-5 shrink-0 text-base-content/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
            </svg>
            <label class="sr-only" for="game-search-input">Search games</label>
            <input
                id="game-search-input"
                type="search"
                wire:model.live.debounce.200ms="query"
                placeholder="Search games…"
                class="spotlight-input h-14 w-full bg-transparent text-base text-base-content placeholder:text-base-content/40 focus:outline-none"
                aria-label="Search games"
                autocomplete="off"
            />
            <div wire:loading wire:target="query" class="shrink-0">
                <span class="loading loading-spinner loading-xs text-base-content/40"></span>
            </div>
            <kbd class="pointer-events-none hidden shrink-0 rounded border border-base-content/15 bg-base-200 px-1.5 py-0.5 font-sans text-[11px] text-base-content/50 sm:inline-flex">Esc</kbd>
        </div>

        {{-- Results --}}
        <div class="max-h-[min(60vh,420px)] overflow-y-auto overscroll-contain">
            @if (trim($query) !== '' && $this->results->isNotEmpty())
                <ul class="p-2" role="listbox" aria-label="Search results">
                    @foreach($this->results as $result)
                        @php $game = $result->game; @endphp
                        <li
                            data-game-url="{{ route('games.show', $game) }}"
                            role="option"
                            :aria-selected="highlightedIndex === {{ $loop->index }}"
                            :class="highlightedIndex === {{ $loop->index }} ? 'bg-base-200' : 'hover:bg-base-200/60'"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors"
                            wire:key="result-{{ $game->id }}"
                        >
                            {{-- Cover --}}
                            <a href="{{ route('games.show', $game) }}" class="shrink-0 focus:outline-none" tabindex="-1">
                                @if ($game->cover_image)
                                    <img src="{{ $game->cover_image }}" alt="" class="h-14 w-10 rounded-lg object-cover ring-1 ring-base-content/10" />
                                @else
                                    <div class="flex h-14 w-10 items-center justify-center rounded-lg bg-base-300 text-xs font-bold text-base-content/50">
                                        {{ mb_substr($game->title, 0, 1) }}
                                    </div>
                                @endif
                            </a>

                            {{-- Info --}}
                            <a href="{{ route('games.show', $game) }}" class="min-w-0 flex-1 focus:outline-none">
                                <p class="truncate font-medium text-base-content">{{ $game->title }}</p>
                                <p class="mt-0.5 truncate text-xs text-base-content/50">
                                    @if ($game->release_date)
                                        {{ $game->release_date->format('Y') }}
                                        @if (count($game->platforms) > 0)
                                            · {{ implode(', ', array_slice($game->platforms, 0, 2)) }}
                                        @endif
                                    @elseif (count($game->platforms) > 0)
                                        {{ implode(', ', array_slice($game->platforms, 0, 2)) }}
                                    @else
                                        &nbsp;
                                    @endif
                                </p>
                            </a>

                            {{-- Track button --}}
                            <div class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100" x-on:click.stop wire:key="track-{{ $game->id }}">
                                @guest
                                    <a href="{{ route('login') }}" class="btn btn-ghost btn-xs" x-on:click.stop>Sign in to track</a>
                                @else
                                    @if ($result->isTracked)
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs text-base-content/60"
                                            wire:click="untrackGame('{{ $game->id }}')"
                                            wire:loading.attr="disabled"
                                            aria-label="Stop tracking {{ $game->title }}"
                                        >
                                            <span wire:loading.remove wire:target="untrackGame('{{ $game->id }}')">Tracked ✓</span>
                                            <span wire:loading wire:target="untrackGame('{{ $game->id }}')">…</span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs"
                                            wire:click="trackGame('{{ $game->id }}')"
                                            wire:loading.attr="disabled"
                                            aria-label="Track {{ $game->title }}"
                                        >
                                            <span wire:loading.remove wire:target="trackGame('{{ $game->id }}')">Track</span>
                                            <span wire:loading wire:target="trackGame('{{ $game->id }}')">…</span>
                                        </button>
                                    @endif
                                @endguest
                            </div>
                        </li>
                    @endforeach
                </ul>

            @elseif (trim($query) !== '' && $this->results->isEmpty())
                <div class="flex flex-col items-center gap-3 px-4 py-10 text-center">
                    <p class="text-sm text-base-content/50">No games found for "<span class="text-base-content/70">{{ $query }}</span>"</p>
                    <a href="{{ route('game-requests.index', ['title' => $query]) }}" class="btn btn-primary btn-sm" @click="$dispatch('close-game-search')">
                        Request "{{ $query }}"
                    </a>
                </div>

            @else
                <div class="px-4 py-6 text-center text-sm text-base-content/40">
                    Start typing to search games…
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center gap-4 border-t border-base-content/10 px-4 py-2.5 text-[11px] text-base-content/40">
            <span class="flex items-center gap-1"><kbd class="rounded border border-base-content/15 bg-base-200 px-1 py-0.5">↑↓</kbd> navigate</span>
            <span class="flex items-center gap-1"><kbd class="rounded border border-base-content/15 bg-base-200 px-1 py-0.5">↵</kbd> open</span>
            <span class="flex items-center gap-1"><kbd class="rounded border border-base-content/15 bg-base-200 px-1 py-0.5">Esc</kbd> close</span>
        </div>
    </div>
</div>
