<?php

use App\Models\Game;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

new class extends Component
{
    use AuthorizesRequests;

    public string $query = '';

    /**
     * @return \Illuminate\Support\Collection<int, Game>
     */
    public function getResultsProperty(): \Illuminate\Support\Collection
    {
        if (trim($this->query) === '') {
            return collect();
        }

        return Game::query()
            ->where('title', 'like', '%'.trim($this->query).'%')
            ->limit(10)
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function getTrackedGameIdsProperty(): array
    {
        if (! auth()->check() || $this->results->isEmpty()) {
            return [];
        }

        return auth()->user()
            ->trackedGames()
            ->whereIn('game_id', $this->results->pluck('id'))
            ->pluck('game_id')
            ->all();
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

<div class="flex flex-col overflow-hidden rounded-2xl" role="search" aria-label="Search games">
    <div class="shrink-0 px-4 pt-4 pb-2">
        <flux:input
            type="search"
            wire:model.live.debounce.300ms="query"
            placeholder="Search games…"
            class="w-full"
            autofocus
            aria-label="Search games"
        />
    </div>
    <div>
        @if(trim($query) !== '')
            <div wire:loading class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">Searching…</div>
        @endif
        <div wire:loading.remove>
            <ul class="flex max-h-[60vh] flex-col gap-2 overflow-y-auto py-2 pr-2 pl-4" role="list">
                @foreach($this->results as $game)
            @php
                $isTracked = in_array($game->id, $this->trackedGameIds);
            @endphp
            <li class="search-result-item flex items-center gap-3 rounded-lg border border-zinc-200/50 bg-white px-4 py-3 shadow-sm transition-colors hover:bg-zinc-100 dark:border-zinc-700/50 dark:bg-zinc-900 dark:hover:bg-zinc-800" style="animation-delay: {{ $loop->index * 0.03 }}s">
                <a
                    href="{{ route('games.show', $game) }}"
                    class="flex min-w-0 flex-1 items-center gap-3 focus:outline-none"
                >
                    @if ($game->cover_image)
                        <img src="{{ $game->cover_image }}" alt="" class="h-16 w-12 shrink-0 rounded-lg object-cover ring-1 ring-zinc-200/50 shadow-sm dark:ring-zinc-700/50" />
                    @else
                        <div class="flex h-16 w-12 shrink-0 items-center justify-center rounded-lg bg-zinc-200/80 text-sm font-semibold text-zinc-500 ring-1 ring-zinc-200/50 shadow-sm dark:bg-zinc-700/80 dark:text-zinc-400 dark:ring-zinc-700/50">{{ substr($game->title, 0, 1) }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-zinc-900 dark:text-white">{{ $game->title }}</span>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
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
                        <a href="{{ route('login') }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300" x-on:click.stop>Log in to track</a>
                    @else
                        @if ($isTracked)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="untrackGame({{ $game->id }})"
                                wire:loading.attr="disabled"
                                aria-label="Remove {{ $game->title }} from tracking"
                            >
                                <span wire:loading.remove wire:target="untrackGame">Remove from tracking</span>
                                <span wire:loading wire:target="untrackGame">…</span>
                            </flux:button>
                        @else
                            <flux:button
                                variant="primary"
                                size="sm"
                                wire:click="trackGame({{ $game->id }})"
                                wire:loading.attr="disabled"
                                aria-label="Track {{ $game->title }}"
                            >
                                <span wire:loading.remove wire:target="trackGame">Track game</span>
                                <span wire:loading wire:target="trackGame">…</span>
                            </flux:button>
                        @endif
                    @endguest
                </div>
            </li>
                @endforeach
            </ul>
        </div>
    </div>
    @if (trim($query) === '')
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Type to search games.</p>
    @elseif($this->results->isEmpty())
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No games found.</p>
    @endif
</div>
