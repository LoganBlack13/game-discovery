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
    <h2 class="font-display text-lg font-semibold text-base-content sm:text-xl">Recent updates</h2>
    <p class="text-sm text-base-content/70">Latest news and release updates for your tracked games</p>

    <div class="flex flex-wrap items-center gap-2">
        <label for="feed-filter" class="text-sm font-medium text-base-content">Show</label>
        <select
            id="feed-filter"
            wire:model.live="filter"
            class="select select-bordered select-sm"
            aria-label="Filter feed"
        >
            <option value="all">All updates</option>
            <option value="news">News only</option>
            <option value="release">Release updates only</option>
        </select>
    </div>

    @if (count($this->feedData['items']) > 0)
        <ul class="flex flex-col gap-4" role="list">
            @foreach ($this->feedData['items'] as $index => $item)
                <li
                    class="rounded-box border border-base-content/10 bg-base-100 p-4 shadow-sm transition"
                    style="animation: feed-item-in 0.35s ease-out both; animation-delay: {{ min($index * 0.05, 0.5) }}s;"
                >
                    <div class="flex gap-4">
                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-box bg-base-200">
                            @if ($item['game']->cover_image ?? null)
                                <img src="{{ $item['game']->cover_image }}" alt="" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <span class="font-display text-xl font-bold text-base-content/40">{{ substr($item['game']->title ?? '', 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-sm font-semibold text-base-content">{{ $item['game']->title ?? 'Game' }}</p>
                            @php
                                $badgeClass = match ($item['type']) {
                                    'new_article' => 'badge badge-info badge-sm',
                                    'release_date_changed' => 'badge badge-warning badge-sm',
                                    'release_date_announced' => 'badge badge-success badge-sm',
                                    'game_released' => 'badge badge-success badge-sm',
                                    'major_update' => 'badge badge-secondary badge-sm',
                                    default => 'badge badge-ghost badge-sm',
                                };
                            @endphp
                            <span class="mt-0.5 {{ $badgeClass }}">{{ $item['type_label'] }}</span>
                            <p class="mt-1 text-sm text-base-content/70" title="{{ $item['title'] }}">{{ $item['title'] }}</p>
                            @if (! empty($item['description']))
                                <p class="mt-0.5 text-xs text-base-content/60">{{ $item['description'] }}</p>
                            @endif
                            <p class="mt-1 text-xs text-base-content/60">
                                <time datetime="{{ $item['occurred_at']->toIso8601String() }}">{{ $item['occurred_at']->diffForHumans() }}</time>
                            </p>
                            <a
                                href="{{ $item['url'] }}"
                                @if ($item['type'] === 'new_article') target="_blank" rel="noopener noreferrer" @endif
                                class="mt-2 inline-block text-sm font-medium text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
                                aria-label="{{ $item['type'] === 'new_article' ? 'Read article: ' . $item['title'] : 'View ' . ($item['game']->title ?? 'game') }}"
                                @if ($item['type'] === 'new_article') title="{{ $item['title'] }}" @endif
                            >
                                {{ $item['type'] === 'new_article' ? 'Read article' : 'View game' }} →
                            </a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($this->feedData['hasMore'])
            <div class="pt-2">
                <button type="button" class="btn btn-ghost btn-sm" wire:click="loadMore" aria-label="Load more updates">
                    Load more
                </button>
            </div>
        @endif
    @else
        <p class="text-base-content/70">No updates yet. Track some games to see their news and release updates here.</p>
    @endif
</section>
