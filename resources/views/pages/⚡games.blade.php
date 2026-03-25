<?php

use App\Enums\ReleaseStatus;
use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Title('Games')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function getGamesProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Game::query()
            ->when($this->search !== '', function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%');
            })
            ->when($this->status === 'upcoming', fn ($q) => $q->upcoming()->upcomingByReleaseDate())
            ->when($this->status === 'released', fn ($q) => $q->released()->orderByDesc('release_date'))
            ->when($this->status === '', fn ($q) => $q->upcomingByReleaseDate())
            ->paginate(24);
    }
};
?>

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-8 space-y-1">
        <h1 class="font-display text-3xl font-semibold text-base-content">Games</h1>
        <p class="text-sm text-base-content/70">Browse the catalogue and track what you're waiting for.</p>
    </header>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div role="tablist" class="tabs tabs-box w-fit">
            <button
                role="tab"
                wire:click="$set('status', '')"
                class="tab {{ $status === '' ? 'tab-active' : '' }}"
                aria-selected="{{ $status === '' ? 'true' : 'false' }}"
            >All</button>
            <button
                role="tab"
                wire:click="$set('status', 'upcoming')"
                class="tab {{ $status === 'upcoming' ? 'tab-active' : '' }}"
                aria-selected="{{ $status === 'upcoming' ? 'true' : 'false' }}"
            >Upcoming</button>
            <button
                role="tab"
                wire:click="$set('status', 'released')"
                class="tab {{ $status === 'released' ? 'tab-active' : '' }}"
                aria-selected="{{ $status === 'released' ? 'true' : 'false' }}"
            >Released</button>
        </div>
        <div class="w-full max-w-xs">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search games…"
                class="input input-bordered input-sm w-full"
                aria-label="Search games"
            />
        </div>
    </div>

    {{-- Grid --}}
    @if ($this->games->isEmpty())
        <p class="text-base-content/70">No games found.</p>
    @else
        <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($this->games as $game)
                <a
                    href="{{ route('games.show', $game) }}"
                    class="card compact bg-base-300 border border-base-content/10 overflow-hidden rounded-box transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    aria-label="{{ $game->title }}"
                >
                    <figure class="aspect-[3/4] w-full overflow-hidden">
                        @if ($game->cover_image)
                            <img src="{{ $game->cover_image }}" alt="" class="size-full object-cover" loading="lazy" />
                        @else
                            <div class="flex size-full items-center justify-center bg-base-200">
                                <span class="font-display text-3xl font-bold text-base-content/40">{{ mb_substr($game->title, 0, 1) }}</span>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body gap-0.5 p-3">
                        <h2 class="card-title font-display text-sm font-semibold text-base-content line-clamp-2 leading-tight">{{ $game->title }}</h2>
                        @if ($game->release_date)
                            <p class="text-xs text-base-content/60">{{ $game->release_date->format('M j, Y') }}</p>
                        @elseif ($game->release_status === ReleaseStatus::Announced)
                            <p class="text-xs text-base-content/50">Announced</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $this->games->links() }}
        </div>
    @endif
</div>
