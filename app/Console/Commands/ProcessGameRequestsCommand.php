<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessGameRequestsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ProcessGameRequestsCommand extends Command
{
    protected $signature = 'game-requests:process
                            {--limit=5 : Maximum number of pending requests to process}';

    protected $description = 'Process the most-requested games and add them via the data provider.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $runId = Str::uuid()->toString();

        ProcessGameRequestsJob::dispatch($runId, $limit);

        $this->info("Game request processor dispatched. Run ID: {$runId}");

        return self::SUCCESS;
    }
}
