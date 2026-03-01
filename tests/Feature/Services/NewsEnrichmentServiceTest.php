<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\News;
use App\Services\NewsEnrichmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$stubRssWithMatch = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Test</title>
<item><title>Elden Ring DLC announced</title><link>https://example.com/elden-ring-dlc</link><pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate></item>
<item><title>Unrelated article</title><link>https://example.com/unrelated</link><pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate></item>
</channel></rss>
XML;

test('enrich creates news only for matched games', function () use ($stubRssWithMatch): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test Feed', 'url' => 'https://example.com/feed'],
    ]);

    Http::fake([
        'https://example.com/feed' => Http::response($stubRssWithMatch, 200, ['Content-Type' => 'application/xml']),
    ]);

    $game = Game::factory()->create(['title' => 'Elden Ring']);

    $service = app(NewsEnrichmentService::class);
    $service->enrich(Str::uuid()->toString());

    expect(News::query()->count())->toBe(1);
    $news = News::query()->first();
    expect($news->game_id)->toBe($game->id)
        ->and($news->title)->toBe('Elden Ring DLC announced')
        ->and($news->url)->toBe('https://example.com/elden-ring-dlc')
        ->and($news->source)->toBe('Test Feed');
});

test('enrich does not create duplicate news for same game and url', function () use ($stubRssWithMatch): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test Feed', 'url' => 'https://example.com/feed'],
    ]);

    Http::fake([
        'https://example.com/feed' => Http::response($stubRssWithMatch, 200, ['Content-Type' => 'application/xml']),
    ]);

    Game::factory()->create(['title' => 'Elden Ring']);

    $service = app(NewsEnrichmentService::class);
    $service->enrich(Str::uuid()->toString());
    $service->enrich(Str::uuid()->toString());

    expect(News::query()->count())->toBe(1);
});
