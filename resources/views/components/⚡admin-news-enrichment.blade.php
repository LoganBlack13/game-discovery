<?php

use App\Jobs\EnrichNewsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?string $runId = null;

    /** @var 'idle'|'running'|'completed'|'failed' */
    public string $status = 'idle';

    /**
     * @var array{status?: string, current_feed_name?: string|null, current_feed_url?: string|null, feeds_total?: int, feeds_done?: int, last_matched?: array<int, array{game_title: string, news_title: string}>, created_count?: int, error?: string|null}
     */
    public array $progress = [];

    public function startRun(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->runId = Str::uuid()->toString();
        $this->status = 'running';
        $this->progress = [];

        EnrichNewsJob::dispatch($this->runId);
    }

    public function refreshProgress(): void
    {
        if ($this->runId === null || $this->status !== 'running') {
            return;
        }

        $key = "news_enrichment:progress:{$this->runId}";
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

<div class="flex flex-col gap-4">
    @if ($status === 'idle')
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Crawl configured RSS feeds and attach news only to games in the database.
        </p>
        <flux:button wire:click="startRun">
            Run enrichment
        </flux:button>
    @else
        @if ($status === 'running')
            <div wire:poll.2s="refreshProgress" class="flex flex-col gap-3">
                @if (! empty($progress['current_feed_name']))
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Crawling: {{ $progress['current_feed_name'] }}
                    </p>
                @endif
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400" aria-busy="true">
                    <svg class="size-5 animate-spin shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Enriching…</span>
                </div>
                @if (count($progress['last_matched'] ?? []) > 0)
                    <ul class="max-h-64 list-inside space-y-1 overflow-y-auto rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400" aria-live="polite">
                        @foreach ($progress['last_matched'] as $match)
                            <li><span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $match['game_title'] }}</span> – {{ $match['news_title'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if ($status === 'completed')
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3" aria-live="polite">
                <p class="text-sm font-medium text-green-600 dark:text-green-400">Completed</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Created {{ $progress['created_count'] ?? 0 }} news items from {{ $progress['feeds_done'] ?? 0 }}/{{ $progress['feeds_total'] ?? 0 }} feeds.
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
