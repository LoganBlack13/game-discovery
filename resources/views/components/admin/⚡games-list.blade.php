<?php

use App\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $source = '';

    /**
     * @return LengthAwarePaginator<Game>
     */
    public function getGamesProperty(): LengthAwarePaginator
    {
        return Game::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->source !== '', fn ($query) => $query->where('external_source', $this->source))
            ->latest()
            ->paginate(20);
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('open-edit-drawer', gameId: $id);
    }

    public function deleteGame(int $id): void
    {
        $game = Game::query()->findOrFail($id);
        $this->authorize('delete', $game);
        $game->delete();
        $this->dispatch('game-deleted');
    }
};
?>

<div class="flex flex-col gap-4" role="region" aria-label="Games list">
    <div class="flex flex-wrap items-center gap-4">
        <flux:input
            type="search"
            placeholder="Search by title or slug…"
            wire:model.live.debounce.300ms="search"
            aria-label="Search games"
            class="min-w-[12rem]"
        />
        <select
            wire:model.live="source"
            aria-label="Filter by source"
            class="min-w-[10rem] rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
        >
            <option value="">All sources</option>
            <option value="rawg">RAWG</option>
        </select>
    </div>

    <flux:table :paginate="$this->games->hasPages() ? $this->games : null">
        <flux:table.columns>
            <flux:table.column>Cover</flux:table.column>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Release date</flux:table.column>
            <flux:table.column>Source</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->games as $game)
                <flux:table.row :key="$game->id">
                    <flux:table.cell>
                        @if ($game->cover_image)
                            <img src="{{ $game->cover_image }}" alt="" class="h-10 w-10 shrink-0 rounded object-cover" loading="lazy">
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 text-xs">—</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        <a href="{{ route('games.show', $game) }}" class="hover:underline">{{ $game->title }}</a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $game->release_date?->format('M j, Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $game->external_source ? ucfirst($game->external_source) : '—' }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $game->id }})" aria-label="Edit {{ $game->title }}">Edit</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="deleteGame({{ $game->id }})" wire:confirm="Are you sure you want to delete this game?" aria-label="Delete {{ $game->title }}">Delete</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 dark:text-zinc-400">No games found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
