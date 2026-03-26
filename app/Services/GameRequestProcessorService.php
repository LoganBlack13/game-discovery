<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProviderResolver;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use App\Models\GameRequest;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class GameRequestProcessorService
{
    private const string DEFAULT_SOURCE = 'rawg';

    private const int PROGRESS_TTL_SECONDS = 3600;

    public function __construct(
        private GameDataProviderResolver $resolver
    ) {}

    public function process(int $limit = 5, ?string $runId = null): void
    {
        $requests = GameRequest::query()
            ->pending()
            ->orderByDesc('request_count')
            ->limit($limit)
            ->get();

        $processed = 0;
        $added = 0;
        $progressKey = $runId !== null ? 'game_requests:progress:'.$runId : null;

        if ($progressKey !== null) {
            $this->writeProgress($progressKey, 'running', null, 0, 0, null);
        }

        try {
            foreach ($requests as $request) {
                if ($progressKey !== null) {
                    $this->writeProgress($progressKey, 'running', $request->display_title, $processed, $added, null);
                }

                $existingByTitle = $this->findExistingGameByTitle($request->normalized_title);
                if ($existingByTitle instanceof Game) {
                    $this->markRequestAdded($request, $existingByTitle->id);
                    $processed++;
                    $added++;

                    continue;
                }

                $provider = $this->resolver->resolve(self::DEFAULT_SOURCE);
                $results = $provider->search($request->display_title);

                if ($results === []) {
                    $processed++;

                    continue;
                }

                $first = $results[0];
                $externalId = $first['external_id'];
                $externalSource = $first['external_source'];

                $existingByExternal = Game::query()
                    ->where('external_source', $externalSource)
                    ->where('external_id', $externalId)
                    ->first();

                if ($existingByExternal !== null) {
                    $this->markRequestAdded($request, $existingByExternal->id);
                    $processed++;
                    $added++;

                    continue;
                }

                dispatch_sync(new SyncGameJob($externalId, $externalSource));

                $game = Game::query()
                    ->where('external_source', $externalSource)
                    ->where('external_id', $externalId)
                    ->first();

                if ($game !== null) {
                    $this->markRequestAdded($request, $game->id);
                    $added++;
                }

                $processed++;
            }

            if ($progressKey !== null) {
                $this->writeProgress($progressKey, 'completed', null, $processed, $added, null);
            }
        } catch (Throwable $throwable) {
            if ($progressKey !== null) {
                $this->writeProgress($progressKey, 'failed', null, $processed, $added, $throwable->getMessage());
            }

            throw $throwable;
        }
    }

    private function findExistingGameByTitle(string $normalizedTitle): ?Game
    {
        return Game::query()
            ->get()
            ->first(fn (Game $g): bool => GameRequestNormalizer::normalize($g->title) === $normalizedTitle);
    }

    private function markRequestAdded(GameRequest $request, int $gameId): void
    {
        $request->update([
            'game_id' => $gameId,
            'status' => 'added',
            'added_at' => now(),
        ]);
    }

    private function writeProgress(
        string $key,
        string $status,
        ?string $currentTitle,
        int $processed,
        int $added,
        ?string $error
    ): void {
        Cache::put($key, [
            'status' => $status,
            'current_title' => $currentTitle,
            'processed' => $processed,
            'added' => $added,
            'error' => $error,
        ], self::PROGRESS_TTL_SECONDS);
    }
}
