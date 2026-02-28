<?php

use App\Contracts\GameDataProvider;
use Livewire\Component;

new class extends Component
{
    public string $query = '';

    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function getSearchResultsProperty(): array
    {
        if (trim($this->query) === '') {
            return [];
        }

        $provider = app(GameDataProvider::class);

        return $provider->search(trim($this->query));
    }
};
?>

<div class="flex flex-col gap-4" role="search">
    <flux:input
        type="search"
        wire:model.live.debounce.1000ms="query"
        placeholder="Search RAWG for a game…"
        class="w-full"
        aria-label="Search RAWG"
    />
    <div class="min-h-[120px]" aria-label="RAWG search results">
        @if (trim($query) === '')
            <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">Enter a game name to search RAWG.</p>
        @else
            <div wire:loading.flex class="flex items-center gap-2 py-4 text-sm text-zinc-500 dark:text-zinc-400" wire:target="query">
                <flux:spinner size="sm" />
                <span>Searching…</span>
            </div>
            <div wire:loading.remove wire:target="query">
                @if (count($this->searchResults) > 0)
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700" role="list">
                        @foreach($this->searchResults as $item)
                            <li class="flex gap-3 px-2 py-3">
                                @if (!empty($item['cover_image']))
                                    <img src="{{ $item['cover_image'] }}" alt="" class="h-14 w-10 shrink-0 rounded object-cover" />
                                @else
                                    <div class="flex h-14 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ substr($item['title'], 0, 1) }}</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $item['title'] }}</span>
                                    @if (!empty($item['release_date']))
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($item['release_date'])->format('M j, Y') }}</p>
                                    @endif
                                    @if (count($item['platforms'] ?? []) > 0)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($item['platforms'], 0, 3)) }}</p>
                                    @endif
                                    @if (count($item['genres'] ?? []) > 0)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($item['genres'], 0, 3)) }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0">
                                    <flux:button size="sm" disabled>Add</flux:button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">No RAWG results. Try another query.</p>
                @endif
            </div>
        @endif
    </div>
</div>
