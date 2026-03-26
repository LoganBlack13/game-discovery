<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class NewsEnrichmentService
{
    private const int MAX_LAST_MATCHED = 50;

    public function __construct(
        private RssFeedFetcher $fetcher,
        private NewsGameMatcher $matcher
    ) {}

    public function enrich(string $runId): void
    {
        $feeds = config('news_enrichment.feeds', []);
        $ttl = (int) config('news_enrichment.progress_ttl_seconds', 3600);
        $key = 'news_enrichment:progress:'.$runId;

        $createdCount = 0;
        $lastMatched = [];
        $currentFeedIndex = 0;
        $currentFeedName = null;
        $currentFeedUrl = null;

        $this->writeProgress($key, [
            'status' => 'running',
            'current_feed_name' => null,
            'current_feed_url' => null,
            'feeds_total' => count($feeds),
            'feeds_done' => 0,
            'last_matched' => [],
            'created_count' => 0,
            'error' => null,
        ], $ttl);

        try {
            foreach ($feeds as $index => $feed) {
                $currentFeedIndex = $index;
                $name = $feed['name'] ?? 'Unknown';
                $currentFeedName = $name;
                $url = $feed['url'] ?? '';
                $currentFeedUrl = $url;

                $this->writeProgress($key, [
                    'status' => 'running',
                    'current_feed_name' => $name,
                    'current_feed_url' => $url,
                    'feeds_total' => count($feeds),
                    'feeds_done' => $index,
                    'last_matched' => $lastMatched,
                    'created_count' => $createdCount,
                    'error' => null,
                ], $ttl);

                try {
                    $items = $this->fetcher->fetch($url);
                } catch (Throwable $e) { // @codeCoverageIgnore
                    Log::warning('News enrichment: failed to fetch feed', [ // @codeCoverageIgnore
                        'feed' => $name, // @codeCoverageIgnore
                        'url' => $url, // @codeCoverageIgnore
                        'message' => $e->getMessage(), // @codeCoverageIgnore
                    ]); // @codeCoverageIgnore
                    $items = []; // @codeCoverageIgnore
                } // @codeCoverageIgnore

                foreach ($items as $item) {
                    $game = $this->matcher->findMatchingGame($item['title']);
                    if (! $game instanceof Game) {
                        continue;
                    }

                    $exists = News::query()
                        ->where('game_id', $game->id)
                        ->where('url', $item['url'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    News::query()->create([
                        'game_id' => $game->id,
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'source' => $name,
                        'thumbnail' => $item['thumbnail'] ?? null,
                        'published_at' => $item['published_at'] instanceof Carbon
                            ? $item['published_at']
                            : null,
                    ]);
                    $createdCount++;

                    $lastMatched[] = [
                        'game_title' => $game->title,
                        'news_title' => $item['title'],
                    ];
                    if (count($lastMatched) > self::MAX_LAST_MATCHED) { // @codeCoverageIgnore
                        array_shift($lastMatched); // @codeCoverageIgnore
                    } // @codeCoverageIgnore
                }

                $this->writeProgress($key, [
                    'status' => 'running',
                    'current_feed_name' => $name,
                    'current_feed_url' => $url,
                    'feeds_total' => count($feeds),
                    'feeds_done' => $index + 1,
                    'last_matched' => $lastMatched,
                    'created_count' => $createdCount,
                    'error' => null,
                ], $ttl);
            }

            $this->writeProgress($key, [
                'status' => 'completed',
                'current_feed_name' => null,
                'current_feed_url' => null,
                'feeds_total' => count($feeds),
                'feeds_done' => count($feeds),
                'last_matched' => $lastMatched,
                'created_count' => $createdCount,
                'error' => null,
            ], $ttl);
        } catch (Throwable $throwable) { // @codeCoverageIgnore
            $this->writeProgress($key, [ // @codeCoverageIgnore
                'status' => 'failed', // @codeCoverageIgnore
                'current_feed_name' => $currentFeedName, // @codeCoverageIgnore
                'current_feed_url' => $currentFeedUrl, // @codeCoverageIgnore
                'feeds_total' => count($feeds), // @codeCoverageIgnore
                'feeds_done' => $currentFeedIndex, // @codeCoverageIgnore
                'last_matched' => $lastMatched, // @codeCoverageIgnore
                'created_count' => $createdCount, // @codeCoverageIgnore
                'error' => $throwable->getMessage(), // @codeCoverageIgnore
            ], $ttl); // @codeCoverageIgnore
            throw $throwable; // @codeCoverageIgnore
        } // @codeCoverageIgnore
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeProgress(string $key, array $payload, int $ttl): void
    {
        Cache::put($key, $payload, $ttl);
    }
}
