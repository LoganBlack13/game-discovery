<?php

declare(strict_types=1);

use App\Models\EnrichmentRun;
use App\Models\Game;
use App\Services\NewsEnrichmentService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$stubRss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Test</title>
<item><title>Elden Ring DLC announced</title><link>https://example.com/elden-ring-dlc</link><pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate></item>
</channel></rss>
XML;

test('a completed enrichment run is persisted to the database', function () use ($stubRss): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test Feed', 'url' => 'https://example.com/feed'],
    ]);
    Http::fake(['https://example.com/feed' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml'])]);
    Game::factory()->create(['title' => 'Elden Ring']);

    $runId = Str::uuid()->toString();
    resolve(NewsEnrichmentService::class)->enrich($runId);

    $run = EnrichmentRun::query()->where('run_id', $runId)->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('completed')
        ->and($run->feeds_total)->toBe(1)
        ->and($run->feeds_done)->toBe(1)
        ->and($run->created_count)->toBe(1)
        ->and($run->finished_at)->not->toBeNull();
});

test('enrichment run tracks zero created count when no matches', function () use ($stubRss): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test Feed', 'url' => 'https://example.com/feed'],
    ]);
    Http::fake(['https://example.com/feed' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml'])]);
    // No game — nothing will match

    $runId = Str::uuid()->toString();
    resolve(NewsEnrichmentService::class)->enrich($runId);

    $run = EnrichmentRun::query()->where('run_id', $runId)->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('completed')
        ->and($run->created_count)->toBe(0);
});
