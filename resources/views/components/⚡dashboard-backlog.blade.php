<?php

use App\Enums\BacklogPriority;
use App\Enums\TrackedGameStatus;
use App\Models\TrackedGame;
use App\Models\User;
use App\Services\PersonalTrackingService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    private const int PAGE_SIZE = 20;

    public string $platform = '';

    public string $availability = '';

    public int $limit = self::PAGE_SIZE;

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, TrackedGame>
     */
    public function getPlayNextProperty(): Illuminate\Database\Eloquent\Collection
    {
        return $this->backlogQuery()->where('is_up_next', true)->get();
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, TrackedGame>
     */
    public function getBacklogProperty(): Illuminate\Database\Eloquent\Collection
    {
        return $this->backlogQuery()->where('is_up_next', false)->limit($this->limit)->get();
    }

    public function getBacklogCountProperty(): int
    {
        return $this->backlogQuery()->where('is_up_next', false)->count();
    }

    #[On('tracking-updated')]
    public function refreshBacklog(): void
    {
        // Re-render when a status changes elsewhere on the dashboard.
    }

    public function updatedPlatform(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function updatedAvailability(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function showMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function togglePlayNext(int $entryId): void
    {
        $entry = $this->findEntry($entryId);
        $entry->update(['is_up_next' => ! $entry->is_up_next]);
    }

    public function setPriority(int $entryId, string $priority): void
    {
        $this->findEntry($entryId)->update(['priority' => BacklogPriority::tryFrom($priority)]);
    }

    public function move(int $entryId, int $direction): void
    {
        app(PersonalTrackingService::class)->moveInBacklog($this->findEntry($entryId), $direction);
    }

    public function start(int $entryId): void
    {
        app(PersonalTrackingService::class)->changeStatus($this->findEntry($entryId), TrackedGameStatus::Playing);
        $this->dispatch('tracking-updated');
    }

    private function findEntry(int $entryId): TrackedGame
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user->trackedGameEntries()->whereKey($entryId)->firstOrFail();
    }

    /**
     * @return Illuminate\Database\Eloquent\Relations\HasMany<TrackedGame, User>
     */
    private function backlogQuery(): Illuminate\Database\Eloquent\Relations\HasMany
    {
        $user = auth()->user();
        assert($user instanceof User);

        $query = $user->trackedGameEntries()
            ->withStatus(TrackedGameStatus::ToPlay)
            ->with('game')
            ->inBacklogOrder();

        if ($this->platform !== '') {
            $query->whereHas('game', fn (Builder $game) => $game->whereJsonContains('platforms', $this->platform));
        }

        if ($this->availability === 'released') {
            $query->whereHas('game', fn (Builder $game) => $game->released());
        } elseif ($this->availability === 'upcoming') {
            $query->whereHas('game', fn (Builder $game) => $game->upcoming());
        }

        return $query;
    }
};
?>

<section id="backlog" aria-labelledby="backlog-title" class="mb-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="backlog-title" class="font-display text-lg font-semibold text-base-content sm:text-xl">My backlog</h2>
            <p class="mt-1 text-sm text-base-content/70">Pin a few games to “Play next” and order the rest as you like.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label for="backlog-platform" class="sr-only">Platform</label>
            <select id="backlog-platform" wire:model.live="platform" class="select select-bordered select-sm">
                <option value="">All platforms</option>
                <option value="PC">PC</option>
                <option value="PlayStation">PlayStation</option>
                <option value="Xbox">Xbox</option>
                <option value="Switch">Switch</option>
            </select>
            <label for="backlog-availability" class="sr-only">Availability</label>
            <select id="backlog-availability" wire:model.live="availability" class="select select-bordered select-sm">
                <option value="">Any availability</option>
                <option value="released">Available now</option>
                <option value="upcoming">Not released yet</option>
            </select>
        </div>
    </div>

    @if ($this->playNext->isEmpty() && $this->backlogCount === 0)
        <p class="mt-4 text-sm text-base-content/70">
            @if ($platform !== '' || $availability !== '')
                No “To Play” game matches these filters.
            @else
                Your backlog is empty. Set a tracked game to “To Play” to plan it here.
            @endif
        </p>
    @else
        @if ($this->playNext->isNotEmpty())
            <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-primary">Play next</h3>
            <ul class="mt-2 flex flex-col divide-y divide-base-content/10 rounded-box border border-primary/30 bg-primary/5" role="list" data-play-next>
                @foreach ($this->playNext as $entry)
                    @include('components.dashboard.backlog-row', ['entry' => $entry, 'isFirst' => $loop->first, 'isLast' => $loop->last])
                @endforeach
            </ul>
        @endif

        @if ($this->backlog->isNotEmpty())
            <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-base-content/70">To play ({{ $this->backlogCount }})</h3>
            <ul class="mt-2 flex flex-col divide-y divide-base-content/10 rounded-box border border-base-content/10" role="list" data-backlog>
                @foreach ($this->backlog as $entry)
                    @include('components.dashboard.backlog-row', ['entry' => $entry, 'isFirst' => $loop->first, 'isLast' => $loop->last && $this->backlog->count() >= $this->backlogCount])
                @endforeach
            </ul>
            @if ($this->backlog->count() < $this->backlogCount)
                <button type="button" wire:click="showMore" class="btn btn-ghost btn-sm mt-2">Show more</button>
            @endif
        @endif
    @endif
</section>
