<?php

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use App\Services\CompanionMomentService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public const int RECENT_LIMIT = 10;

    public int $gameId;

    public bool $showAll = false;

    public function mount(Game $game): void
    {
        $this->gameId = $game->id;
    }

    #[Computed]
    public function hasDevice(): bool
    {
        return $this->user()->companionDevices()->active()->exists();
    }

    /**
     * @return array{count: int, active_seconds: int}
     */
    #[Computed]
    public function stats(): array
    {
        $sessions = $this->user()->gameSessions()->where('game_id', $this->gameId);

        return [
            'count' => (clone $sessions)->count(),
            'active_seconds' => (int) $sessions->sum('active_seconds'),
        ];
    }

    /**
     * @return Collection<int, GameSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        return $this->user()->gameSessions()
            ->where('game_id', $this->gameId)
            ->with('device:id,label')
            ->latest('started_at')
            ->when(! $this->showAll, fn ($query) => $query->limit(self::RECENT_LIMIT))
            ->get();
    }

    #[Computed]
    public function isExcluded(): bool
    {
        return $this->user()->companionExcludedGames()->whereKey($this->gameId)->exists();
    }

    #[Computed]
    public function declaredHours(): ?int
    {
        $hours = $this->user()->trackedGameEntries()->where('game_id', $this->gameId)->value('playtime_hours');

        return is_numeric($hours) ? (int) $hours : null;
    }

    public function toggleExclusion(): void
    {
        $excluded = $this->user()->companionExcludedGames();

        if ($this->isExcluded) {
            $excluded->detach($this->gameId);
        } else {
            $excluded->attach($this->gameId);
        }

        unset($this->isExcluded);
    }

    public function deleteSession(string $sessionId, CompanionMomentService $moments): void
    {
        $session = GameSession::query()->findOrFail($sessionId);
        $this->authorize('delete', $session);

        $moments->deleteForSession($session);
        $session->delete();
        $this->dispatch('companion-moments-changed');

        unset($this->sessions, $this->stats);
    }

    public function showAllSessions(): void
    {
        $this->showAll = true;
        unset($this->sessions);
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
    @if ($this->hasDevice || $this->stats['count'] > 0)
        <section id="companion-sessions" aria-labelledby="companion-sessions-title" class="mt-10 border-t border-base-content/10 pt-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="companion-sessions-title" class="font-display text-xl font-semibold text-base-content">Sessions</h2>
                @php $live = $this->sessions->first(fn ($session) => $session->isLive()); @endphp
                @if ($live)
                    <span class="badge badge-primary gap-1">Playing now · {{ \App\Models\GameSession::humanDuration($live->active_seconds) }}</span>
                @endif
            </div>

            <div class="stats stats-vertical mt-4 w-full border border-base-300 bg-base-200/40 sm:stats-horizontal">
                <div class="stat">
                    <div class="stat-title">Measured playtime</div>
                    <div class="stat-value text-2xl">{{ \App\Models\GameSession::humanDuration($this->stats['active_seconds']) }}</div>
                    <div class="stat-desc">{{ $this->stats['count'] }} {{ Str::plural('session', $this->stats['count']) }} recorded by Questlog Companion</div>
                </div>
                @if ($this->declaredHours !== null)
                    <div class="stat">
                        <div class="stat-title">Declared playtime</div>
                        <div class="stat-value text-2xl">{{ $this->declaredHours }} h</div>
                        <div class="stat-desc">Entered in your review</div>
                    </div>
                @endif
                @if ($this->sessions->isNotEmpty())
                    <div class="stat">
                        <div class="stat-title">Last session</div>
                        <div class="stat-value text-2xl">{{ \App\Models\GameSession::humanDuration($this->sessions->first()->active_seconds) }}</div>
                        <div class="stat-desc">{{ $this->sessions->first()->started_at->diffForHumans() }}</div>
                    </div>
                @endif
            </div>

            @if ($this->sessions->isEmpty())
                <p class="mt-4 text-sm text-base-content/60">No session recorded yet. Launch the game with Questlog Companion running.</p>
            @else
                <ul class="mt-4 flex flex-col gap-2">
                    @foreach ($this->sessions as $session)
                        <li wire:key="session-{{ $session->id }}" class="flex items-center justify-between gap-3 rounded-box border border-base-300 bg-base-200/40 px-4 py-2 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-base-content">
                                    {{ $session->started_at->isoFormat('LLL') }}
                                    · {{ \App\Models\GameSession::humanDuration($session->active_seconds) }}
                                    @if ($session->isLive())
                                        <span class="badge badge-primary badge-sm">Playing now</span>
                                    @endif
                                </p>
                                <p class="truncate text-xs text-base-content/60">{{ $session->device?->label ?? 'Removed device' }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="deleteSession('{{ $session->id }}')"
                                wire:confirm="Delete this session and its moments? It will no longer count in your measured playtime."
                                class="btn btn-ghost btn-xs"
                                aria-label="Delete session of {{ $session->started_at->isoFormat('LLL') }}"
                            >Delete</button>
                        </li>
                    @endforeach
                </ul>
                @if (! $showAll && $this->stats['count'] > $this->sessions->count())
                    <button type="button" wire:click="showAllSessions" class="btn btn-ghost btn-sm mt-2">Show all {{ $this->stats['count'] }} sessions</button>
                @endif
            @endif

            @if ($this->hasDevice)
                <label class="label mt-4 cursor-pointer justify-start gap-3">
                    <input type="checkbox" class="toggle toggle-sm" wire:click="toggleExclusion" @checked($this->isExcluded) />
                    <span class="label-text">Don’t track this game with Companion</span>
                </label>
            @endif
        </section>
    @endif
</div>
