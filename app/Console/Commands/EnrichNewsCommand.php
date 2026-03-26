<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NewsEnrichmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Override;

final class EnrichNewsCommand extends Command
{
    #[Override]
    protected $signature = 'news:enrich';

    #[Override]
    protected $description = 'Crawl configured RSS feeds and attach news to games in the database.';

    public function handle(NewsEnrichmentService $service): int
    {
        $runId = Str::uuid()->toString();

        $service->enrich($runId);

        $key = 'news_enrichment:progress:'.$runId;
        /** @var array{feeds_done: int, feeds_total: int, created_count: int}|null $progress */
        $progress = Cache::get($key);

        if ($progress !== null) {
            $feedsDone = $progress['feeds_done'];
            $feedsTotal = $progress['feeds_total'];
            $created = $progress['created_count'];
            $this->info(sprintf('Enriched %d/%d feeds, created %d news items.', $feedsDone, $feedsTotal, $created));
        } else {
            $this->info('News enrichment completed.'); // @codeCoverageIgnore
        }

        return self::SUCCESS;
    }
}
