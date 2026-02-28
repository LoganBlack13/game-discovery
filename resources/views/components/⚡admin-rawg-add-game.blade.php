<?php

use App\Contracts\GameDataProvider;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use Livewire\Component;

new class extends Component
{
    public string $query = '';

    public ?string $addedExternalId = null;

    public ?string $addedTitle = null;

    public ?string $addError = null;

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

    /**
     * @return array<int, string>
     */
    public function getAlreadyInDbExternalIdsProperty(): array
    {
        $results = $this->searchResults;
        if ($results === []) {
            return [];
        }
        $externalIds = array_column($results, 'external_id');

        return Game::query()
            ->where('external_source', 'rawg')
            ->whereIn('external_id', $externalIds)
            ->pluck('external_id')
            ->all();
    }

    public function addGame(string $externalId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->addError = null;
        $this->addedExternalId = null;
        $this->addedTitle = null;

        try {
            SyncGameJob::dispatchSync($externalId);
            $title = null;
            foreach ($this->searchResults as $item) {
                if (($item['external_id'] ?? '') === $externalId) {
                    $title = $item['title'] ?? 'Unknown';
                    break;
                }
            }
            $this->addedExternalId = $externalId;
            $this->addedTitle = $title ?? 'Unknown';
        } catch (\Throwable) {
            $this->addError = 'Could not add game. Try again.';
        }
    }
};
?>

<div class="flex flex-col gap-4" role="search">
    <div aria-live="polite" class="min-h-[1.5rem]">
        @if ($addedTitle)
            <p class="text-sm text-green-600 dark:text-green-400">Added: {{ $addedTitle }}</p>
        @endif
        @if ($addError)
            <p class="text-sm text-red-600 dark:text-red-400">{{ $addError }}</p>
        @endif
    </div>
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
            <div wire:loading.flex class="flex items-center gap-2 py-4 text-sm text-zinc-500 dark:text-zinc-400" wire:target="query" aria-busy="true">
                <flux:spinner size="sm" />
                <span>Searching…</span>
            </div>
            <div wire:loading.remove wire:target="query">
                @if (count($this->searchResults) > 0)
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700" role="list" aria-label="RAWG search results">
                        @foreach($this->searchResults as $index => $item)
                            <li class="admin-result-item flex gap-3 px-2 py-3" style="animation-delay: {{ $index * 40 }}ms;">
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
                                    @if (in_array($item['external_id'], $this->alreadyInDbExternalIds))
                                        <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400">Already in database</span>
                                    @else
                                        <flux:button
                                            size="sm"
                                            wire:click="addGame({{ json_encode($item['external_id']) }})"
                                            wire:loading.attr="disabled"
                                            wire:target="addGame"
                                        >
                                            <span wire:loading.remove wire:target="addGame">Add to database</span>
                                            <span wire:loading wire:target="addGame">Adding…</span>
                                        </flux:button>
                                    @endif
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
