<?php

use App\Contracts\GameDataProviderResolver;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use Livewire\Component;

new class extends Component
{
    public string $query = '';

    public ?string $addedExternalId = null;

    public ?string $addedTitle = null;

    public ?string $addError = null;

    private const int SEARCH_LIMIT = 10;

    /**
     * @return array{rawg: list<array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>, igdb: list<array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>}
     */
    public function getSearchResultsBySourceProperty(): array
    {
        if (trim($this->query) === '') {
            return ['rawg' => [], 'igdb' => []];
        }

        $resolver = app(GameDataProviderResolver::class);
        $query = trim($this->query);
        $bySource = ['rawg' => [], 'igdb' => []];

        foreach (array_keys($bySource) as $source) {
            try {
                $provider = $resolver->resolve($source);
                $results = $provider->search($query);
                $bySource[$source] = array_slice($results, 0, self::SEARCH_LIMIT);
            } catch (\Throwable) {
                continue;
            }
        }

        return $bySource;
    }

    /**
     * @return array<int, string> "external_source:external_id" pairs that exist in DB
     */
    public function getAlreadyInDbPairsProperty(): array
    {
        $bySource = $this->searchResultsBySource;
        $existing = [];
        foreach ($bySource as $source => $items) {
            $ids = array_unique(array_map(fn (array $item): string => (string) ($item['external_id'] ?? ''), $items));
            $ids = array_filter($ids);
            if ($ids === []) {
                continue;
            }
            $found = Game::query()
                ->where('external_source', $source)
                ->whereIn('external_id', $ids)
                ->pluck('external_id')
                ->all();
            foreach ($found as $id) {
                $existing[] = $source.':'.$id;
            }
        }

        return $existing;
    }

    /**
     * @return array<string, \Carbon\CarbonInterface|null> Map of "source:id" => last_synced_at for games in DB from current results
     */
    public function getLastSyncedAtByPairProperty(): array
    {
        $bySource = $this->searchResultsBySource;
        $map = [];
        foreach ($bySource as $source => $items) {
            $ids = array_unique(array_map(fn (array $item): string => (string) ($item['external_id'] ?? ''), $items));
            $ids = array_filter($ids);
            if ($ids === []) {
                continue;
            }
            $games = Game::query()
                ->where('external_source', $source)
                ->whereIn('external_id', $ids)
                ->get(['external_id', 'last_synced_at']);
            foreach ($games as $game) {
                $map[$source.':'.$game->external_id] = $game->last_synced_at;
            }
        }

        return $map;
    }

    public function addGame(string $externalId, string $externalSource): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->addError = null;
        $this->addedExternalId = null;
        $this->addedTitle = null;

        try {
            SyncGameJob::dispatchSync($externalId, $externalSource);
            $title = null;
            foreach ($this->searchResultsBySource[$externalSource] ?? [] as $item) {
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
        placeholder="Search games (RAWG, IGDB)…"
        class="w-full"
        aria-label="Search games"
    />
    <div class="min-h-[120px]" aria-label="Game search results">
        @if (trim($query) === '')
            <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">Enter a game name to search RAWG and IGDB.</p>
        @else
            <div wire:loading.flex class="flex items-center gap-2 py-4 text-sm text-zinc-500 dark:text-zinc-400" wire:target="query" aria-busy="true">
                <svg class="size-5 animate-spin shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Searching…</span>
            </div>
            <div wire:loading.remove wire:target="query" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @foreach (['rawg' => 'RAWG', 'igdb' => 'IGDB'] as $sourceKey => $sourceLabel)
                    <section aria-label="{{ $sourceLabel }} results" class="flex flex-col gap-2">
                        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $sourceLabel }}</h2>
                        @php
                            $items = $this->searchResultsBySource[$sourceKey] ?? [];
                        @endphp
                        @if (count($items) > 0)
                            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700" role="list" aria-label="{{ $sourceLabel }} results">
                                @foreach ($items as $index => $item)
                                    @php
                                        $pairKey = ($item['external_source'] ?? '') . ':' . ($item['external_id'] ?? '');
                                        $isInDb = in_array($pairKey, $this->alreadyInDbPairs);
                                        $lastSynced = $this->lastSyncedAtByPair[$pairKey] ?? null;
                                    @endphp
                                    <li class="admin-result-item flex gap-3 px-2 py-3" style="animation-delay: {{ $index * 40 }}ms;">
                                        @if (!empty($item['cover_image']))
                                            <img src="{{ $item['cover_image'] }}" alt="" class="h-14 w-10 shrink-0 rounded object-cover" />
                                        @else
                                            <div class="flex h-14 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ substr($item['title'], 0, 1) }}</div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-zinc-900 dark:text-white">{{ $item['title'] }}</span>
                                            </div>
                                            @if (!empty($item['release_date']))
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($item['release_date'])->format('M j, Y') }}</p>
                                            @endif
                                            @if (count($item['platforms'] ?? []) > 0)
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($item['platforms'], 0, 3)) }}</p>
                                            @endif
                                            @if (count($item['genres'] ?? []) > 0)
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', array_slice($item['genres'], 0, 3)) }}</p>
                                            @endif
                                            @if ($isInDb && $lastSynced)
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Last synced: {{ $lastSynced->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                        <div class="shrink-0 flex flex-col gap-1 items-end">
                                            @if ($isInDb)
                                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400">Already in database</span>
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    wire:click="addGame({{ json_encode($item['external_id']) }}, {{ json_encode($item['external_source']) }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="addGame"
                                                >
                                                    <span wire:loading.remove wire:target="addGame">Update</span>
                                                    <span wire:loading wire:target="addGame">Updating…</span>
                                                </flux:button>
                                            @else
                                                <flux:button
                                                    size="sm"
                                                    wire:click="addGame({{ json_encode($item['external_id']) }}, {{ json_encode($item['external_source']) }})"
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
                            <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">No {{ $sourceLabel }} results.</p>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>
