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

test('fetch returns empty array when response body is not valid XML', function (): void {
    Http::fake([
        '*' => Http::response('this is not xml', 200),
    ]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items)->toBeArray()->and($items)->toBeEmpty();
});

test('fetch returns null published_at when pubDate is absent', function (): void {
    $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel>
<item><title>No Date Item</title><link>https://example.com/1</link></item>
</channel></rss>
XML;
    Http::fake(['*' => Http::response($rss, 200)]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items[0]['published_at'])->toBeNull();
});

test('fetch extracts thumbnail from media:content element', function (): void {
    $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"><channel>
<item>
  <title>Media Item</title>
  <link>https://example.com/1</link>
  <media:content url="https://example.com/thumb.jpg" medium="image"/>
</item>
</channel></rss>
XML;
    Http::fake(['*' => Http::response($rss, 200)]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items[0]['thumbnail'])->toBe('https://example.com/thumb.jpg');
});

test('fetch extracts thumbnail from enclosure element with image type', function (): void {
    $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel>
<item>
  <title>Enclosure Item</title>
  <link>https://example.com/1</link>
  <enclosure url="https://example.com/cover.png" type="image/png" length="12345"/>
</item>
</channel></rss>
XML;
    Http::fake(['*' => Http::response($rss, 200)]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items[0]['thumbnail'])->toBe('https://example.com/cover.png');
});

test('fetch returns empty array on failed request', function (): void {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    $fetcher = app(RssFeedFetcher::class);
    $items = $fetcher->fetch('https://example.com/feed.xml');

    expect($items)->toBeArray()->and($items)->toBeEmpty();
});
