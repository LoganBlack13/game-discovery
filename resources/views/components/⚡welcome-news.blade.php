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

<section aria-label="Latest news" class="space-y-0">
    <h2 class="font-display text-sm font-bold uppercase tracking-widest text-primary mb-6">Latest news</h2>

    @if ($this->items['items']->isEmpty())
        <p class="text-sm text-base-content/70">No news yet. Check back later.</p>
    @else
        <ul class="flex flex-col" role="list">
            @foreach ($this->items['items'] as $item)
                <li class="flex gap-4 border-b border-base-content/10 py-4 first:pt-0 last:border-b-0">
                    <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-base-300">
                        @if ($item->thumbnail)
                            <img src="{{ $item->thumbnail }}" alt="" class="size-full object-cover" />
                        @elseif ($item->game?->cover_image)
                            <img src="{{ $item->game->cover_image }}" alt="" class="size-full object-cover" />
                        @else
                            <div class="flex size-full items-center justify-center">
                                <span class="font-display text-xl font-bold text-base-content/50">{{ $item->game ? substr($item->game->title ?? '', 0, 1) : '?' }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <a
                            href="{{ $item->url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block font-semibold text-base-content focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-200"
                            title="{{ $item->title }}"
                        >
                            <span class="line-clamp-2">{{ $item->title }}</span>
                        </a>
                        <p class="mt-0.5 text-xs text-base-content/60">
                            @if ($item->source)
                                {{ $item->source }}
                                @if ($item->published_at)
                                    — <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                @endif
                            @elseif ($item->game)
                                <a href="{{ route('games.show', $item->game) }}" class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    {{ $item->game->title }}
                                </a>
                                @if ($item->published_at)
                                    — <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                @endif
                            @else
                                @if ($item->published_at)
                                    <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('M j, Y') }}</time>
                                @endif
                            @endif
                        </p>
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
