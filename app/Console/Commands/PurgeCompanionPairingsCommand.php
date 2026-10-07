<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CompanionPairing;
use Illuminate\Console\Command;
use Override;

final class PurgeCompanionPairingsCommand extends Command
{
    #[Override]
    protected $signature = 'companion:purge-pairings';

    #[Override]
    protected $description = 'Delete Questlog Companion pairing requests expired for more than a day.';

    public function handle(): int
    {
        /** @var int $deleted */
        $deleted = CompanionPairing::query()
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $this->info("Deleted {$deleted} expired pairing(s).");

        return self::SUCCESS;
    }
}
