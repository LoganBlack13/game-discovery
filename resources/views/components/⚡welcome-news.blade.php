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
        $limit = $this->perPage * $this->page + 1;
        $items = News::query()
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
    <h2 class="font-display text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 mb-6">Latest news</h2>

    @if ($this->items['items']->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">No news yet. Check back later.</p>
    @else
        <ul class="flex flex-col gap-3" role="list">
            @foreach ($this->items['items'] as $item)
                <li class="shrink-0 rounded-xl border border-zinc-200/80 bg-white/90 p-4 shadow-sm backdrop-blur-sm transition-shadow dark:border-white/10 dark:bg-white/5 dark:shadow-none hover:shadow-md dark:hover:shadow-none dark:hover:bg-white/[0.07]">
                    <div class="flex gap-3">
                        @if ($item->game)
                            <div class="h-14 w-10 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-700">
                                @if ($item->game->cover_image)
                                    <img src="{{ $item->game->cover_image }}" alt="" class="h-full w-full object-cover" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <span class="font-display text-lg font-bold text-zinc-400 dark:text-zinc-500">{{ substr($item->game->title ?? '', 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <a
                                href="{{ $item->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900"
                                title="{{ $item->title }}"
                            >
                                <p class="font-medium text-zinc-900 dark:text-white line-clamp-2">{{ $item->title }}</p>
                            </a>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($item->game)
                                    <a
                                        href="{{ route('games.show', $item->game) }}"
                                        class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                                    >
                                        {{ $item->game->title }}
                                    </a>
                                    @if ($item->published_at)
                                        · <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                    @endif
                                @else
                                    @if ($item->published_at)
                                        <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
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
