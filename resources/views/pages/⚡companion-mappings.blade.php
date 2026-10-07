<?php

use App\Enums\MappingCandidateStatus;
use App\Models\CompanionMappingCandidate;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\GameSession;
use App\Models\User;
use App\Services\CompanionMappingService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Companion games')] class extends Component
{
    public const int SEARCH_LIMIT = 8;

    /** Candidate whose game is being searched. */
    public ?int $searchingCandidateId = null;

    /** Personal mapping being pointed to another game. */
    public ?int $reassigningMappingId = null;

    public string $search = '';

    public bool $movePastSessions = false;

    /**
     * @return Collection<int, CompanionMappingCandidate>
     */
    #[Computed]
    public function candidates(): Collection
    {
        return $this->user()->companionMappingCandidates()->awaitingUser()->with('proposedGame:id,title,slug')->latest('last_seen_at')->get();
    }

    /**
     * @return Collection<int, CompanionMappingCandidate>
     */
    #[Computed]
    public function ignored(): Collection
    {
        return $this->user()->companionMappingCandidates()->where('status', MappingCandidateStatus::Ignored)->latest('last_seen_at')->get();
    }

    /**
     * @return Collection<int, GameExecutableMapping>
     */
    #[Computed]
    public function mappings(): Collection
    {
        return GameExecutableMapping::query()->where('user_id', $this->user()->id)->with('game:id,title,slug')->latest()->get();
    }

    /**
     * @return Collection<int, Game>
     */
    #[Computed]
    public function searchResults(): Collection
    {
        $term = mb_trim($this->search);

        return mb_strlen($term) < 2
            ? new Collection
            : Game::query()->searchByTitle($term)->orderBy('title')->limit(self::SEARCH_LIMIT)->get(['id', 'title', 'slug']);
    }

    public function confirmProposal(int $candidateId, CompanionMappingService $mappings): void
    {
        $candidate = $this->candidate($candidateId);
        $game = Game::query()->findOrFail($candidate->proposed_game_id);

        $mappings->confirm($candidate, $game);
        unset($this->candidates, $this->mappings);
    }

    public function startSearch(int $candidateId): void
    {
        $candidate = $this->candidate($candidateId);
        $this->reset('reassigningMappingId', 'movePastSessions');
        $this->searchingCandidateId = $candidate->id;
        $this->search = $candidate->label();
    }

    public function startReassign(int $mappingId): void
    {
        $mapping = GameExecutableMapping::query()->findOrFail($mappingId);
        $this->authorize('update', $mapping);
        $this->reset('searchingCandidateId', 'movePastSessions');
        $this->reassigningMappingId = $mapping->id;
        $this->search = '';
    }

    public function cancelSearch(): void
    {
        $this->reset('searchingCandidateId', 'reassigningMappingId', 'search', 'movePastSessions');
    }

    public function chooseGame(int $gameId, CompanionMappingService $mappings): void
    {
        $game = Game::query()->findOrFail($gameId);

        if ($this->searchingCandidateId !== null) {
            $mappings->confirm($this->candidate($this->searchingCandidateId), $game);
        } elseif ($this->reassigningMappingId !== null) {
            $mapping = GameExecutableMapping::query()->findOrFail($this->reassigningMappingId);
            $this->authorize('update', $mapping);
            $mappings->reassign($mapping, $game, $this->movePastSessions);
        }

        $this->cancelSearch();
        unset($this->candidates, $this->mappings);
    }

    public function ignore(int $candidateId, CompanionMappingService $mappings): void
    {
        $mappings->ignore($this->candidate($candidateId));
        unset($this->candidates, $this->ignored);
    }

    public function restore(int $candidateId, CompanionMappingService $mappings): void
    {
        $mappings->restore($this->candidate($candidateId));
        unset($this->candidates, $this->ignored);
    }

    public function deleteMapping(int $mappingId): void
    {
        $mapping = GameExecutableMapping::query()->findOrFail($mappingId);
        $this->authorize('delete', $mapping);

        $mapping->delete();
        unset($this->mappings);
    }

    private function candidate(int $candidateId): CompanionMappingCandidate
    {
        $candidate = CompanionMappingCandidate::query()->findOrFail($candidateId);
        $this->authorize('update', $candidate);

        return $candidate;
    }

    private function user(): User
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user;
    }
};
?>

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-8 space-y-1">
        <h1 class="font-display text-3xl font-semibold text-base-content">Companion games</h1>
        <p class="text-sm text-base-content/70">Tell Questlog Companion which game an unknown program is. Sessions already played are sent once the game is chosen.</p>
    </header>

    <section aria-labelledby="to-associate-title">
        <h2 id="to-associate-title" class="font-display text-lg font-semibold text-base-content">To associate</h2>

        @if ($this->candidates->isEmpty())
            <p class="mt-2 text-sm text-base-content/60">Nothing to associate. Unknown games you play will appear here.</p>
        @else
            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($this->candidates as $candidate)
                    <li wire:key="candidate-{{ $candidate->id }}" class="card border border-base-300 bg-base-200/40">
                        <div class="card-body gap-3 p-4">
                            <div>
                                <p class="font-medium text-base-content">{{ $candidate->label() }}</p>
                                <p class="text-xs text-base-content/60">
                                    {{ $candidate->executable_name }}@if ($candidate->path_fragment) · {{ $candidate->path_fragment }}@endif
                                    @if ($candidate->launcher) · {{ ucfirst($candidate->launcher->value) }}@endif
                                    · played {{ GameSession::humanDuration($candidate->total_seconds) }}
                                </p>
                            </div>

                            @if ($searchingCandidateId === $candidate->id)
                                @include('pages.partials.companion-game-search')
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @if ($candidate->proposedGame)
                                        <button type="button" wire:click="confirmProposal({{ $candidate->id }})" class="btn btn-primary btn-sm">It is {{ $candidate->proposedGame->title }}</button>
                                    @endif
                                    <button type="button" wire:click="startSearch({{ $candidate->id }})" class="btn btn-ghost btn-sm">{{ $candidate->proposedGame ? 'Another game' : 'Choose the game' }}</button>
                                    <a href="{{ route('game-requests.index') }}" class="btn btn-ghost btn-sm">Request a missing game</a>
                                    <button type="button" wire:click="ignore({{ $candidate->id }})" class="btn btn-ghost btn-sm text-base-content/60">Not a game</button>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="mappings-title" class="mt-10 border-t border-base-content/10 pt-10">
        <h2 id="mappings-title" class="font-display text-lg font-semibold text-base-content">Your associations</h2>

        @if ($this->mappings->isEmpty())
            <p class="mt-2 text-sm text-base-content/60">No personal association yet.</p>
        @else
            <ul class="mt-4 flex flex-col gap-2">
                @foreach ($this->mappings as $mapping)
                    <li wire:key="mapping-{{ $mapping->id }}" class="rounded-box border border-base-300 bg-base-200/40 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-base-content">{{ $mapping->game?->title }}</p>
                                <p class="truncate text-xs text-base-content/60">
                                    {{ $mapping->executable_name }}@if ($mapping->path_fragment) · {{ $mapping->path_fragment }}@endif
                                    @if ($mapping->launcher) · {{ ucfirst($mapping->launcher->value) }}@endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="startReassign({{ $mapping->id }})" class="btn btn-ghost btn-xs">Change game</button>
                                <button
                                    type="button"
                                    wire:click="deleteMapping({{ $mapping->id }})"
                                    wire:confirm="Remove this association? Companion will stop recognising this program."
                                    class="btn btn-ghost btn-xs"
                                >Remove</button>
                            </div>
                        </div>
                        @if ($reassigningMappingId === $mapping->id)
                            <div class="mt-3">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" class="checkbox checkbox-sm" wire:model="movePastSessions" />
                                    <span class="label-text text-sm">Also move the sessions already recorded</span>
                                </label>
                                @include('pages.partials.companion-game-search')
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($this->ignored->isNotEmpty())
        <section aria-labelledby="ignored-title" class="mt-10 border-t border-base-content/10 pt-10">
            <h2 id="ignored-title" class="font-display text-lg font-semibold text-base-content">Not games</h2>
            <ul class="mt-4 flex flex-col gap-2">
                @foreach ($this->ignored as $candidate)
                    <li wire:key="ignored-{{ $candidate->id }}" class="flex items-center justify-between gap-3 rounded-box border border-base-300 px-4 py-2 text-sm">
                        <span class="truncate">{{ $candidate->label() }} <span class="text-xs text-base-content/60">· {{ $candidate->executable_name }}</span></span>
                        <button type="button" wire:click="restore({{ $candidate->id }})" class="btn btn-ghost btn-xs">Restore</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
