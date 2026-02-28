<?php

use App\Services\DashboardFeedService;
use Livewire\Component;

new class extends Component
{
    public string $filter = 'all';

    public int $page = 1;

    public int $perPage = 15;

    /**
     * @return array{items: array<int, array>, hasMore: bool}
     */
    public function getFeedDataProperty(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return ['items' => [], 'hasMore' => false];
        }

        $service = app(DashboardFeedService::class);
        $limit = $this->perPage * $this->page + 1;
        $result = $service->getFeedItems($user, $this->filter, $limit, 0);

        return [
            'items' => array_slice($result, 0, $this->perPage * $this->page),
            'hasMore' => count($result) > $this->perPage * $this->page,
        ];
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    public function updatedFilter(): void
    {
        $this->page = 1;
    }
};
?>

<section aria-label="Recent updates" class="space-y-4">
    <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white sm:text-xl">Recent updates</h2>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">Latest news and release updates for your tracked games</p>

    <div class="flex flex-wrap items-center gap-2">
        <label for="feed-filter" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Show</label>
        <select
            id="feed-filter"
            wire:model.live="filter"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            aria-label="Filter feed"
        >
            <option value="all">All updates</option>
            <option value="news">News only</option>
            <option value="release">Release updates only</option>
        </select>
    </div>

    @if (count($this->feedData['items']) > 0)
        <ul class="flex flex-col gap-3" role="list">
            @foreach ($this->feedData['items'] as $item)
                <li class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex gap-4">
                        @if ($item['game']->cover_image ?? null)
                            <img src="{{ $item['game']->cover_image }}" alt="" class="h-16 w-12 shrink-0 rounded object-cover" />
                        @else
                            <div class="flex h-16 w-12 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700">
                                <span class="font-display text-lg font-bold text-zinc-400 dark:text-zinc-500">{{ substr($item['game']->title ?? '', 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-sm font-semibold text-zinc-900 dark:text-white">{{ $item['game']->title ?? 'Game' }}</p>
                            <span class="mt-0.5 inline-block rounded bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-300">{{ $item['type_label'] }}</span>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $item['title'] }}</p>
                            @if (! empty($item['description']))
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">{{ $item['description'] }}</p>
                            @endif
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <time datetime="{{ $item['occurred_at']->toIso8601String() }}">{{ $item['occurred_at']->diffForHumans() }}</time>
                            </p>
                            <a href="{{ $item['url'] }}" class="mt-2 inline-block text-sm font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500" aria-label="{{ $item['type'] === 'new_article' ? 'Read article: ' . $item['title'] : 'View game' }}">
                                {{ $item['type'] === 'new_article' ? 'Read article' : 'View game' }} →
                            </a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($this->feedData['hasMore'])
            <div class="pt-2">
                <flux:button wire:click="loadMore" variant="ghost" size="sm" aria-label="Load more updates">
                    Load more
                </flux:button>
            </div>
        @endif
    @else
        <p class="text-zinc-500 dark:text-zinc-400">No updates yet. Track some games to see their news and release updates here.</p>
    @endif
</section>
