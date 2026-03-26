<?php

declare(strict_types=1);

use App\Jobs\EnrichNewsJob;
use App\Models\Game;
use App\Models\News;
use App\Services\NewsEnrichmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$stubRss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Test</title>
<item><title>Elden Ring update</title><link>https://example.com/1</link><pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate></item>
</channel></rss>
XML;

test('job runs enrichment and progress is completed in cache', function () use ($stubRss): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test Feed', 'url' => 'https://example.com/feed'],
    ]);
    Http::fake([
        'https://example.com/feed' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml']),
    ]);

    Game::factory()->create(['title' => 'Elden Ring']);
    $runId = Str::uuid()->toString();

    $job = new EnrichNewsJob($runId);
    $job->handle(resolve(NewsEnrichmentService::class));

    $progress = Cache::get('news_enrichment:progress:'.$runId);
    expect($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('completed')
        ->and($progress['created_count'])->toBe(1);
    expect(News::query()->count())->toBe(1);
});
