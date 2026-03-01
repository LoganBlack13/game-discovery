<?php

declare(strict_types=1);

use App\Services\RssFeedFetcher;
use Illuminate\Support\Facades\Http;

$stubRss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test Feed</title>
    <item>
      <title>Elden Ring DLC announced</title>
      <link>https://example.com/elden-ring-dlc</link>
      <pubDate>Mon, 01 Mar 2026 12:00:00 +0000</pubDate>
    </item>
    <item>
      <title>Another game news</title>
      <link>https://example.com/another</link>
      <pubDate>Tue, 02 Mar 2026 10:00:00 GMT</pubDate>
    </item>
  </channel>
</rss>
XML;

test('fetch returns array of items with title url and published_at', function () use ($stubRss): void {
    Http::fake([
        '*' => Http::response($stubRss, 200, ['Content-Type' => 'application/xml']),
    ]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items)->toBeArray()
        ->and($items)->toHaveCount(2)
        ->and($items[0])->toHaveKeys(['title', 'url', 'published_at'])
        ->and($items[0]['title'])->toBe('Elden Ring DLC announced')
        ->and($items[0]['url'])->toBe('https://example.com/elden-ring-dlc')
        ->and($items[1]['title'])->toBe('Another game news')
        ->and($items[1]['url'])->toBe('https://example.com/another');
});

test('fetch returns empty array on failed request', function (): void {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items)->toBeArray()->and($items)->toBeEmpty();
});
