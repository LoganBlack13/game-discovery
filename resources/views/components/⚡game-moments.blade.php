<?php

use App\Http\Requests\Companion\UpdateCompanionMomentsRequest;
use App\Models\Game;
use App\Models\SavedMoment;
use App\Models\User;
use App\Services\CompanionMomentService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public const int RECENT_LIMIT = 12;

    public int $gameId;

    public bool $showAll = false;

    public ?string $editingId = null;

    public string $caption = '';

    public function mount(Game $game): void
    {
        $this->gameId = $game->id;
    }

    #[Computed]
    public function count(): int
    {
        return $this->user()->savedMoments()->where('game_id', $this->gameId)->count();
    }

    /**
     * @return Collection<int, SavedMoment>
     */
    #[Computed]
    public function moments(): Collection
    {
        return $this->user()->savedMoments()
            ->where('game_id', $this->gameId)
            ->with('session:id,started_at')
            ->latest('captured_at')
            ->when(! $this->showAll, fn ($query) => $query->limit(self::RECENT_LIMIT))
            ->get();
    }

    public function showAllMoments(): void
    {
        $this->showAll = true;
        unset($this->moments);
    }

    public function editCaption(string $momentId): void
    {
        $moment = $this->findMoment($momentId);
        $this->authorize('update', $moment);

        $this->editingId = $moment->id;
        $this->caption = $moment->caption ?? '';
    }

    public function cancelCaption(): void
    {
        $this->reset('editingId', 'caption');
    }

    public function saveCaption(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $this->validate(UpdateCompanionMomentsRequest::captionRules(), UpdateCompanionMomentsRequest::livewireMessages());

        $moment = $this->findMoment($this->editingId);
        $this->authorize('update', $moment);

        $caption = mb_trim($this->caption);
        $moment->forceFill(['caption' => $caption === '' ? null : $caption])->save();

        $this->cancelCaption();
        unset($this->moments);
    }

    public function deleteMoment(string $momentId, CompanionMomentService $moments): void
    {
        $moment = $this->findMoment($momentId);
        $this->authorize('delete', $moment);

        $moments->delete($moment);

        if ($this->editingId === $momentId) {
            $this->cancelCaption();
        }
        $this->refreshMoments();
    }

    #[On('companion-moments-changed')]
    public function refreshMoments(): void
    {
        unset($this->moments, $this->count);
    }

    private function findMoment(string $momentId): SavedMoment
    {
        return SavedMoment::query()->findOrFail($momentId);
    }

    private function user(): User
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user;
    }
};
?>

<div>
    @if ($this->count > 0)
        <section id="companion-moments" aria-labelledby="companion-moments-title" class="mt-10 border-t border-base-content/10 pt-10">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="companion-moments-title" class="font-display text-xl font-semibold text-base-content">Moments</h2>
                <p class="text-sm text-base-content/60">{{ $this->count }} {{ Str::plural('moment', $this->count) }} saved with Questlog Companion</p>
            </div>

            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->moments as $moment)
                    <li wire:key="moment-{{ $moment->id }}" class="card card-compact overflow-hidden border border-base-300 bg-base-200/40">
                        <a href="{{ route('moments.image', $moment) }}" target="_blank" rel="noopener" class="block aspect-video bg-base-300">
                            <img
                                src="{{ route('moments.image', ['moment' => $moment, 'size' => 'thumbnail']) }}"
                                alt="{{ $moment->caption ?? 'Moment of '.$moment->captured_at->isoFormat('LLL') }}"
                                loading="lazy"
                                class="size-full object-cover"
                            />
                        </a>
                        <div class="card-body gap-2">
                            <p class="text-xs text-base-content/60">
                                {{ $moment->captured_at->isoFormat('LLL') }}
                                @if ($moment->session)
                                    · session of {{ $moment->session->started_at->isoFormat('ll') }}
                                @endif
                            </p>

                            @if ($editingId === $moment->id)
                                <form wire:submit="saveCaption" class="flex flex-col gap-2">
                                    <textarea wire:model="caption" maxlength="280" rows="2" aria-label="Caption" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                                    @error('caption')
                                        <span class="text-xs text-error">{{ $message }}</span>
                                    @enderror
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-xs">Save</button>
                                        <button type="button" wire:click="cancelCaption" class="btn btn-ghost btn-xs">Cancel</button>
                                    </div>
                                </form>
                            @else
                                @if ($moment->caption)
                                    <p class="text-sm text-base-content">{{ $moment->caption }}</p>
                                @endif
                                <div class="card-actions justify-end">
                                    <button type="button" wire:click="editCaption('{{ $moment->id }}')" class="btn btn-ghost btn-xs">
                                        {{ $moment->caption ? 'Edit caption' : 'Add caption' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deleteMoment('{{ $moment->id }}')"
                                        wire:confirm="Delete this moment? The image will be deleted from Questlog."
                                        class="btn btn-ghost btn-xs text-error"
                                    >Delete</button>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if (! $showAll && $this->count > $this->moments->count())
                <button type="button" wire:click="showAllMoments" class="btn btn-ghost btn-sm mt-2">Show all {{ $this->count }} moments</button>
            @endif
        </section>
    @endif
</div>
