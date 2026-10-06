<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncGameCreditsJob;
use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class SyncGameCreditsCommand extends Command
{
    #[Override]
    protected $signature = 'games:sync-credits
                            {--limit=50 : Maximum number of games to sync}
                            {--game= : Sync a single game by slug}';

    #[Override]
    protected $description = 'Import individual contributors (credits) for games that were never synced or are stale.';

    public function handle(): int
    {
        $slug = $this->option('game');

        $games = is_string($slug) && $slug !== ''
            ? Game::query()->where('slug', $slug)->get(['id'])
            : Game::query()
                ->where(function (Builder $query): void {
                    $query->whereNull('credits_synced_at')
                        ->orWhere('credits_synced_at', '<', now()->subDays(30));
                })
                ->orderByRaw('credits_synced_at IS NOT NULL')
                ->oldest('credits_synced_at')
                ->limit(max(1, (int) $this->option('limit')))
                ->get(['id']);

        foreach ($games as $game) {
            dispatch(new SyncGameCreditsJob($game->id));
        }

        $this->info('Credits sync dispatched for '.$games->count().' game(s).');

        return self::SUCCESS;
    }
}
