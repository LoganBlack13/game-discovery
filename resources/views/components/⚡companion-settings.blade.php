<?php

use App\Http\Requests\Companion\UpdateCompanionMomentsRequest;
use App\Models\Game;
use App\Models\User;
use App\Services\CompanionMomentService;
use App\Services\CompanionSessionService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $trackingEnabled = true;

    public bool $suggestUnknownGames = true;

    public string $momentHotkey = '';

    public bool $momentSound = true;

    public bool $momentUpload = true;

    public bool $hotkeySaved = false;

    public bool $dataDeleted = false;

    public function mount(): void
    {
        $user = $this->user();
        $this->trackingEnabled = $user->companion_tracking_enabled;
        $this->suggestUnknownGames = $user->companion_suggest_unknown_games;
        $this->momentHotkey = $user->companion_moment_hotkey;
        $this->momentSound = $user->companion_moment_sound;
        $this->momentUpload = $user->companion_moment_upload;
    }

    public function updatedTrackingEnabled(bool $enabled): void
    {
        $this->user()->forceFill(['companion_tracking_enabled' => $enabled])->save();
    }

    public function updatedSuggestUnknownGames(bool $enabled): void
    {
        $this->user()->forceFill(['companion_suggest_unknown_games' => $enabled])->save();
    }

    public function saveMomentHotkey(): void
    {
        $this->momentHotkey = mb_trim($this->momentHotkey);
        $this->validate(UpdateCompanionMomentsRequest::hotkeyRules(), UpdateCompanionMomentsRequest::livewireMessages());

        $this->user()->forceFill(['companion_moment_hotkey' => $this->momentHotkey])->save();
        $this->hotkeySaved = true;
    }

    public function updatedMomentSound(bool $enabled): void
    {
        $this->user()->forceFill(['companion_moment_sound' => $enabled])->save();
    }

    public function updatedMomentUpload(bool $enabled): void
    {
        $this->user()->forceFill(['companion_moment_upload' => $enabled])->save();
    }

    /**
     * @return array{used_mb: int, quota_mb: int}
     */
    #[Computed]
    public function momentStorage(): array
    {
        return [
            'used_mb' => (int) ceil(app(CompanionMomentService::class)->usedBytes($this->user()) / 1024 / 1024),
            'quota_mb' => intdiv(CompanionMomentService::QUOTA_BYTES, 1024 * 1024),
        ];
    }

    #[Computed]
    public function pendingCandidates(): int
    {
        return $this->user()->companionMappingCandidates()->awaitingUser()->count();
    }

    /**
     * @return Collection<int, Game>
     */
    #[Computed]
    public function excludedGames(): Collection
    {
        return $this->user()->companionExcludedGames()->orderBy('title')->get(['games.id', 'games.title', 'games.slug']);
    }

    public function includeGame(int $gameId): void
    {
        $this->user()->companionExcludedGames()->detach($gameId);
        unset($this->excludedGames);
    }

    public function deleteAllData(CompanionSessionService $sessions): void
    {
        $sessions->deleteAllFor($this->user());
        $this->dataDeleted = true;
        unset($this->excludedGames, $this->momentStorage);
    }

    private function user(): User
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user;
    }
};
?>

<section>
    <h2 class="text-lg font-medium">Questlog Companion</h2>
    <p class="mt-2 text-sm text-base-content/60">Choose what the desktop app records for you.</p>

    <label class="label mt-4 cursor-pointer justify-start gap-3">
        <input type="checkbox" class="toggle toggle-primary" wire:model.live="trackingEnabled" />
        <span class="label-text">Record my play sessions automatically</span>
    </label>
    @unless ($trackingEnabled)
        <p class="mt-1 text-sm text-warning">Tracking is off: Companion records no new session.</p>
    @endunless

    <label class="label mt-2 cursor-pointer justify-start gap-3">
        <input type="checkbox" class="toggle toggle-primary" wire:model.live="suggestUnknownGames" />
        <span class="label-text">Suggest unknown programs that look like games</span>
    </label>

    <p class="mt-2 text-sm">
        <a href="{{ route('companion.mappings') }}" class="link link-primary">Companion games</a>
        @if ($this->pendingCandidates > 0)
            <span class="badge badge-primary badge-sm ml-1">{{ $this->pendingCandidates }} to associate</span>
        @endif
    </p>

    <h3 class="mt-6 text-sm font-medium">Moments</h3>
    <p class="mt-1 text-sm text-base-content/60">Press the shortcut while playing to save a screenshot of the game. Nothing is captured otherwise.</p>
    <form wire:submit="saveMomentHotkey" class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
        <input type="text" wire:model="momentHotkey" maxlength="40" aria-label="Shortcut to save a moment" class="input input-bordered input-sm sm:w-56" />
        <button type="submit" class="btn btn-ghost btn-sm">Save shortcut</button>
        @if ($hotkeySaved)
            <span class="text-xs text-success">Saved. Companion applies it within 15 minutes.</span>
        @endif
    </form>
    @error('momentHotkey')
        <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @enderror

    <label class="label mt-2 cursor-pointer justify-start gap-3">
        <input type="checkbox" class="toggle toggle-primary" wire:model.live="momentSound" />
        <span class="label-text">Play a sound when a moment is saved</span>
    </label>
    <label class="label mt-2 cursor-pointer justify-start gap-3">
        <input type="checkbox" class="toggle toggle-primary" wire:model.live="momentUpload" />
        <span class="label-text">Send moments to Questlog</span>
    </label>
    @unless ($momentUpload)
        <p class="mt-1 text-sm text-base-content/60">Moments stay on your computer only.</p>
    @endunless
    <p class="mt-1 text-xs text-base-content/60">{{ $this->momentStorage['used_mb'] }} MB used of {{ $this->momentStorage['quota_mb'] }} MB.</p>

    <h3 class="mt-6 text-sm font-medium">Games not tracked</h3>
    @if ($this->excludedGames->isEmpty())
        <p class="mt-1 text-sm text-base-content/60">None. Exclude a game from its page.</p>
    @else
        <ul class="mt-2 flex flex-col gap-2">
            @foreach ($this->excludedGames as $game)
                <li wire:key="excluded-{{ $game->id }}" class="flex items-center justify-between gap-3 rounded-box border border-base-300 bg-base-200/40 px-4 py-2 text-sm">
                    <a href="{{ route('games.show', $game) }}" class="link-hover truncate">{{ $game->title }}</a>
                    <button type="button" wire:click="includeGame({{ $game->id }})" class="btn btn-ghost btn-xs">Track again</button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6">
        @if ($dataDeleted)
            <div role="alert" class="alert alert-success mb-3">
                <span>Companion data deleted. Your statuses, journal and reviews are unchanged.</span>
            </div>
        @endif
        <button
            type="button"
            wire:click="deleteAllData"
            wire:confirm="Delete all sessions, moments, personal game mappings and exclusions recorded by Questlog Companion? Your devices stay connected."
            class="btn btn-error btn-outline btn-sm"
        >Delete all Companion data</button>
    </div>
</section>
