<?php

use App\Models\News;
use Livewire\Component;

new class extends Component
{
    public int $page = 1;

    public int $perPage = 10;

    /**
     * @return array{items: \Illuminate\Support\Collection<int, News>, hasMore: bool}
     */
    public function getItemsProperty(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return ['items' => collect(), 'hasMore' => false];
        }

        $trackedGameIds = $user->trackedGames()->pluck('games.id')->all();
        if ($trackedGameIds === []) {
            return ['items' => collect(), 'hasMore' => false];
        }

        $limit = $this->perPage * $this->page + 1;
        $items = News::query()
            ->whereIn('game_id', $trackedGameIds)
            ->with('game')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return [
            'items' => $items->take($this->perPage * $this->page),
            'hasMore' => $items->count() > $this->perPage * $this->page,
        ];
    }

    public function loadMore(): void
    {
        $this->page++;
    }
};
?>

<section aria-label="Latest news" class="space-y-4">
    <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white sm:text-xl">Latest news</h2>

    @if ($this->items['items']->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">No news yet for your tracked games.</p>
    @else
        <ul class="flex max-h-[calc(100vh-8rem)] flex-col gap-3 overflow-y-auto pr-1" role="list">
            @foreach ($this->items['items'] as $item)
                <li class="shrink-0 rounded-lg border border-zinc-200/80 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <a
                        href="{{ $item->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900"
                    >
                        <p class="font-medium text-zinc-900 dark:text-white line-clamp-2">{{ $item->title }}</p>
                    </a>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        <a
                            href="{{ route('games.show', $item->game) }}"
                            class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                        >
                            {{ $item->game->title }}
                        </a>
                        @if ($item->published_at)
                            · <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                        @endif
                    </p>
                </li>
            @endforeach
            @if ($this->items['hasMore'])
                <li
                    x-data
                    x-intersect.once="$wire.loadMore()"
                    class="min-h-[2rem] shrink-0"
                ></li>
            @endif
        </ul>
    @endif
</section>
