<?php

use App\Jobs\ProcessGameRequestsJob;
use App\Models\GameRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?string $runId = null;

    /** @var 'idle'|'running'|'completed'|'failed' */
    public string $status = 'idle';

    /**
     * @var array{status?: string, current_title?: string|null, processed?: int, added?: int, error?: string|null}
     */
    public array $progress = [];

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, GameRequest>
     */
    public function getRequestsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return GameRequest::query()
            ->with('game')
            ->orderByDesc('request_count')
            ->get();
    }

    public function startRun(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->runId = Str::uuid()->toString();
        $this->status = 'running';
        $this->progress = [];

        ProcessGameRequestsJob::dispatch($this->runId, 5);
    }

    public function refreshProgress(): void
    {
        if ($this->runId === null || $this->status !== 'running') {
            return;
        }

        $key = "game_requests:progress:{$this->runId}";
        $data = Cache::get($key);

        if ($data === null) {
            return;
        }

        $this->progress = $data;
        $runStatus = $data['status'] ?? 'running';
        if (in_array($runStatus, ['completed', 'failed'], true)) {
            $this->status = $runStatus;
        }
    }

    public function resetRun(): void
    {
        $this->runId = null;
        $this->status = 'idle';
        $this->progress = [];
    }
};
?>

<div class="flex flex-col gap-6">
    {{-- Run processor --}}
    <div class="flex flex-col gap-4">
        @if ($status === 'idle')
            <flux:button wire:click="startRun">
                Run processor
            </flux:button>
        @else
            @if ($status === 'running')
                <div wire:poll.2s="refreshProgress" class="flex flex-col gap-3">
                    @if (! empty($progress['current_title']))
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Processing: {{ $progress['current_title'] }}
                        </p>
                    @endif
                    <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400" aria-busy="true">
                        <svg class="size-5 animate-spin shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Processing… {{ $progress['processed'] ?? 0 }} processed, {{ $progress['added'] ?? 0 }} added</span>
                    </div>
                </div>
            @endif

            @if ($status === 'completed')
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3" aria-live="polite">
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Completed</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Processed {{ $progress['processed'] ?? 0 }} requests, added {{ $progress['added'] ?? 0 }} games.
                    </p>
                    <flux:button wire:click="resetRun" class="mt-3">
                        Run again
                    </flux:button>
                </div>
            @endif

            @if ($status === 'failed')
                <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 px-4 py-3" aria-live="polite">
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Failed</p>
                    @if (! empty($progress['error']))
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $progress['error'] }}</p>
                    @endif
                    <flux:button wire:click="resetRun" class="mt-3" variant="danger">
                        Try again
                    </flux:button>
                </div>
            @endif
        @endif
    </div>

    {{-- List of requests --}}
    <div>
        <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">Requests</h2>
        <div class="mt-3 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Title</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Votes</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Game</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @forelse ($this->requests as $req)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                <span class="font-medium">{{ $req->display_title }}</span>
                                @if ($req->display_title !== $req->normalized_title)
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $req->normalized_title }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $req->request_count }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $req->status === 'added' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($req->game)
                                    <a href="{{ route('games.show', $req->game) }}" class="underline hover:no-underline">{{ $req->game->title }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No game requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
