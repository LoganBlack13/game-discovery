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
    <ul class="max-h-[60vh] overflow-y-auto divide-y divide-zinc-200 dark:divide-zinc-700" role="list">
        @foreach($this->results as $game)
            @php
                $isTracked = in_array($game->id, $this->trackedGameIds);
            @endphp
            <li class="flex items-center gap-2 px-4 py-3 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 focus-within:bg-zinc-100 dark:focus-within:bg-zinc-800">
                <a
                    href="{{ route('games.show', $game) }}"
                    class="flex min-w-0 flex-1 gap-3 focus:outline-none"
                >
                    @if ($game->cover_image)
                        <img src="{{ $game->cover_image }}" alt="" class="h-14 w-10 shrink-0 rounded object-cover" />
                    @else
                        <div class="flex h-14 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ substr($game->title, 0, 1) }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $game->title }}</span>
                        @if ($game->release_date)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $game->release_date->format('M j, Y') }}</p>
                        @endif
                        @if (count($game->platforms) > 0)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($game->platforms, 0, 3)) }}</p>
                        @endif
                    </div>
                </a>
                <div class="shrink-0" wire:key="track-{{ $game->id }}">
                    @guest
                        <a href="{{ route('login') }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">Log in to track</a>
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
    @if (trim($query) === '')
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Type to search games.</p>
    @elseif($this->results->isEmpty())
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No games found.</p>
    @endif
</div>