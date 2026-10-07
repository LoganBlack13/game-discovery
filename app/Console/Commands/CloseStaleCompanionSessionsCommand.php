<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CompanionSessionService;
use Illuminate\Console\Command;
use Override;

final class CloseStaleCompanionSessionsCommand extends Command
{
    #[Override]
    protected $signature = 'companion:close-stale-sessions';

    #[Override]
    protected $description = 'Close Questlog Companion sessions without heartbeat for 15 minutes at their last heartbeat.';

    public function handle(CompanionSessionService $sessions): int
    {
        $closed = $sessions->closeStaleSessions();

        $this->info("Closed {$closed} stale session(s).");

        return self::SUCCESS;
    }
}
