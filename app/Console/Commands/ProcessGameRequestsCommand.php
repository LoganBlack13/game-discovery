<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessGameRequestsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Override;

final class ProcessGameRequestsCommand extends Command
{
    #[Override]
    protected $signature = 'game-requests:process
                            {--limit=5 : Maximum number of pending requests to process}';

    #[Override]
    protected $description = 'Process the most-requested games and add them via the data provider.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $runId = Str::uuid()->toString();

        dispatch(new ProcessGameRequestsJob($runId, $limit));

        $this->info('Game request processor dispatched. Run ID: '.$runId);

        return self::SUCCESS;
    }
}
