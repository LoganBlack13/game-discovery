<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class NewsEnrichmentService
{
    private const int MAX_LAST_MATCHED = 50;

    public function __construct(
        private readonly RssFeedFetcher $fetcher,
        private readonly NewsGameMatcher $matcher
    ) {}

    public function enrich(string $runId): void
    {
        $feeds = config('news_enrichment.feeds', []);
        $ttl = (int) config('news_enrichment.progress_ttl_seconds', 3600);
        $key = "news_enrichment:progress:{$runId}";

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
                } catch (Throwable $e) {
                    Log::warning('News enrichment: failed to fetch feed', [
                        'feed' => $name,
                        'url' => $url,
                        'message' => $e->getMessage(),
                    ]);
                    $items = [];
                }

                foreach ($items as $item) {
                    $game = $this->matcher->findMatchingGame($item['title']);
                    if ($game === null) {
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
                    if (count($lastMatched) > self::MAX_LAST_MATCHED) {
                        array_shift($lastMatched);
                    }
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
        } catch (Throwable $e) {
            $this->writeProgress($key, [
                'status' => 'failed',
                'current_feed_name' => $currentFeedName,
                'current_feed_url' => $currentFeedUrl,
                'feeds_total' => count($feeds),
                'feeds_done' => $currentFeedIndex,
                'last_matched' => $lastMatched,
                'created_count' => $createdCount,
                'error' => $e->getMessage(),
            ], $ttl);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeProgress(string $key, array $payload, int $ttl): void
    {
        Cache::put($key, $payload, $ttl);
    }
}
