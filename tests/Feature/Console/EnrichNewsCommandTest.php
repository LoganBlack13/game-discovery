<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

$stubRss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Test</title>
<item><title>Dummy</title><link>https://example.com/1</link><pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate></item>
</channel></rss>
XML;

test('news:enrich command runs and exits successfully', function () use ($stubRss): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test', 'url' => 'https://example.com/feed'],
    ]);
    Http::fake([
        'https://example.com/feed' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml']),
    ]);

    $this->artisan('news:enrich')
        ->assertSuccessful();
});

test('news:enrich output contains summary', function () use ($stubRss): void {
    Config::set('news_enrichment.feeds', [
        ['name' => 'Test', 'url' => 'https://example.com/feed'],
    ]);
    Http::fake([
        'https://example.com/feed' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml']),
    ]);

    $this->artisan('news:enrich')
        ->expectsOutputToContain('feed')
        ->assertSuccessful();
});
