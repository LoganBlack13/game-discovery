<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NewsEnrichmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class EnrichNewsCommand extends Command
{
    protected $signature = 'news:enrich';

    protected $description = 'Crawl configured RSS feeds and attach news to games in the database.';

    public function handle(NewsEnrichmentService $service): int
    {
        $runId = Str::uuid()->toString();

        $service->enrich($runId);

        $key = "news_enrichment:progress:{$runId}";
        $progress = Cache::get($key);

        if ($progress !== null) {
            $feedsDone = $progress['feeds_done'] ?? 0;
            $feedsTotal = $progress['feeds_total'] ?? 0;
            $created = $progress['created_count'] ?? 0;
            $this->info("Enriched {$feedsDone}/{$feedsTotal} feeds, created {$created} news items.");
        } else {
            $this->info('News enrichment completed.'); // @codeCoverageIgnore
        }

        return self::SUCCESS;
    }
}
