<?php

use App\Enums\ReleaseStatus;
use App\Http\Requests\Admin\UpdateGameRequest;
use App\Models\Game;
use App\Services\GameActivityRecorder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $source = '';

    public ?int $editingGameId = null;

    public string $editTitle = '';

    public string $editSlug = '';

    public string $editDescription = '';

    public string $editCoverImage = '';

    public string $editDeveloper = '';

    public string $editPublisher = '';

    public string $editGenres = '';

    public string $editPlatforms = '';

    public string $editReleaseDate = '';

    public string $editReleaseStatus = '';

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

    /**
     * @return Game|null
     */
    public function getEditingGameProperty(): ?Game
    {
        if ($this->editingGameId === null) {
            return null;
        }

        return Game::query()->find($this->editingGameId);
    }

    public function openEdit(int $id): void
    {
        $game = Game::query()->findOrFail($id);
        $this->authorize('update', $game);
        $this->editingGameId = $id;
        $this->editTitle = $game->title;
        $this->editSlug = $game->slug ?? '';
        $this->editDescription = $game->description ?? '';
        $this->editCoverImage = $game->cover_image ?? '';
        $this->editDeveloper = $game->developer ?? '';
        $this->editPublisher = $game->publisher ?? '';
        $this->editGenres = is_array($game->genres) ? implode(', ', $game->genres) : '';
        $this->editPlatforms = is_array($game->platforms) ? implode(', ', $game->platforms) : '';
        $this->editReleaseDate = $game->release_date?->format('Y-m-d') ?? '';
        $this->editReleaseStatus = $game->release_status->value ?? ReleaseStatus::Released->value;
    }

    public function closeEdit(): void
    {
        $this->editingGameId = null;
        $this->reset(['editTitle', 'editSlug', 'editDescription', 'editCoverImage', 'editDeveloper', 'editPublisher', 'editGenres', 'editPlatforms', 'editReleaseDate', 'editReleaseStatus']);
    }

    public function save(GameActivityRecorder $recorder): void
    {
        $game = $this->getEditingGameProperty();
        if ($game === null) {
            return;
        }
        $this->authorize('update', $game);
        $validated = $this->validate(UpdateGameRequest::rulesForGame($game));
        $oldReleaseDate = $game->release_date;
        $oldReleaseStatus = $game->release_status;
        $genres = array_filter(array_map('trim', explode(',', $validated['editGenres'] ?? '')));
        $platforms = array_filter(array_map('trim', explode(',', $validated['editPlatforms'] ?? '')));
        $game->update([
            'title' => $validated['editTitle'],
            'slug' => $validated['editSlug'] !== '' ? $validated['editSlug'] : Str::slug($validated['editTitle']),
            'description' => $validated['editDescription'] !== '' ? $validated['editDescription'] : null,
            'cover_image' => $validated['editCoverImage'] !== '' ? $validated['editCoverImage'] : null,
            'developer' => $validated['editDeveloper'] !== '' ? $validated['editDeveloper'] : null,
            'publisher' => $validated['editPublisher'] !== '' ? $validated['editPublisher'] : null,
            'genres' => $genres,
            'platforms' => $platforms,
            'release_date' => $validated['editReleaseDate'] !== '' ? $validated['editReleaseDate'] : null,
            'release_status' => ReleaseStatus::from($validated['editReleaseStatus']),
        ]);
        $game->refresh();
        $recorder->recordReleaseChanges($game, $oldReleaseDate, $oldReleaseStatus, true);
        $this->closeEdit();
        session()->flash('admin.game.updated', true);
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

    @if ($editingGameId && $this->editingGame)
        <div
            class="fixed inset-0 z-50 flex"
            role="dialog"
            aria-modal="true"
            aria-labelledby="edit-game-title"
        >
            <div
                class="fixed inset-0 bg-zinc-900/50 dark:bg-zinc-950/70"
                wire:click="closeEdit"
                aria-hidden="true"
            ></div>
            <div
                class="relative ml-auto flex h-full w-full max-w-lg flex-col border-s border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-xl overflow-y-auto"
                x-data
                @keydown.escape.window="$wire.closeEdit()"
            >
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3">
                    <h2 id="edit-game-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Edit game</h2>
                    <flux:button variant="ghost" icon="x-mark" size="sm" wire:click="closeEdit" aria-label="Close drawer"></flux:button>
                </div>
                <form wire:submit="save" class="flex flex-1 flex-col gap-4 p-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model="editTitle" aria-label="Title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="editSlug" aria-label="Slug" placeholder="optional" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="editDescription" aria-label="Description" rows="4" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Cover image URL</flux:label>
                        <flux:input type="url" wire:model="editCoverImage" aria-label="Cover image URL" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Developer</flux:label>
                        <flux:input wire:model="editDeveloper" aria-label="Developer" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Publisher</flux:label>
                        <flux:input wire:model="editPublisher" aria-label="Publisher" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Genres (comma-separated)</flux:label>
                        <flux:input wire:model="editGenres" aria-label="Genres" placeholder="e.g. Action, RPG" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Platforms (comma-separated)</flux:label>
                        <flux:input wire:model="editPlatforms" aria-label="Platforms" placeholder="e.g. PC, PlayStation" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Release date</flux:label>
                        <flux:input type="date" wire:model="editReleaseDate" aria-label="Release date" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Release status</flux:label>
                        <select
                            wire:model="editReleaseStatus"
                            aria-label="Release status"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100"
                        >
                            @foreach (ReleaseStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                            @endforeach
                        </select>
                    </flux:field>
                    <div class="mt-auto flex gap-2 pt-4">
                        <flux:button type="button" variant="ghost" wire:click="closeEdit">Cancel</flux:button>
                        <flux:button type="submit">Save</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
