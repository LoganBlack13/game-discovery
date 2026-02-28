<?php

use App\Models\Game;
use Livewire\Component;

new class extends Component
{
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
            <li>
                <a
                    href="{{ route('games.show', $game) }}"
                    class="flex gap-3 px-4 py-3 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:bg-zinc-100 dark:focus:bg-zinc-800 focus:outline-none"
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
            </li>
        @endforeach
    </ul>
    @if (trim($query) === '')
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Type to search games.</p>
    @elseif($this->results->isEmpty())
        <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No games found.</p>
    @endif
</div>