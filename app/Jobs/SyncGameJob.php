<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\GameDataProviderResolver;
use App\Models\Game;
use App\Services\GameActivityRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncGameJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $externalId,
        public string $externalSource,
        public ?int $gameId = null
    ) {}

    public function handle(GameDataProviderResolver $resolver, GameActivityRecorder $recorder): void
    {
        $provider = $resolver->resolve($this->externalSource);
        $details = $provider->getGameDetails($this->externalId);

        $existing = Game::query()
            ->where('external_source', $details['external_source'])
            ->where('external_id', $details['external_id'])
            ->first();

        $incomingNormalized = $this->normalizeDetailsForComparison($details);
        if ($existing !== null) {
            $currentNormalized = $this->normalizeModelForComparison($existing);
            if ($incomingNormalized === $currentNormalized) {
                return;
            }
        }

        $oldReleaseDate = $existing?->release_date;
        $oldReleaseStatus = $existing?->release_status;

        $attributes = [
            'title' => $details['title'],
            'slug' => $details['slug'],
            'description' => $details['description'],
            'cover_image' => $details['cover_image'],
            'developer' => $details['developer'],
            'publisher' => $details['publisher'],
            'genres' => $details['genres'],
            'platforms' => $details['platforms'],
            'release_date' => $details['release_date'],
            'release_status' => $details['release_status'],
            'last_synced_at' => now(),
        ];

        $game = Game::query()->updateOrCreate([
            'external_source' => $details['external_source'],
            'external_id' => $details['external_id'],
        ], $attributes);

        $recorder->recordReleaseChanges(
            $game,
            $oldReleaseDate,
            $oldReleaseStatus,
            $existing !== null
        );
    }

    /**
     * @param  array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}  $details
     * @return array<string, mixed>
     */
    private function normalizeDetailsForComparison(array $details): array
    {
        $genres = $details['genres'];
        $platforms = $details['platforms'];
        sort($genres);
        sort($platforms);

        return [
            'title' => $details['title'],
            'slug' => $details['slug'],
            'description' => $details['description'] ?? '',
            'cover_image' => $details['cover_image'] ?? '',
            'developer' => $details['developer'] ?? '',
            'publisher' => $details['publisher'] ?? '',
            'genres' => $genres,
            'platforms' => $platforms,
            'release_date' => $details['release_date'] ?? '',
            'release_status' => $details['release_status'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeModelForComparison(Game $game): array
    {
        $genres = $game->genres;
        $platforms = $game->platforms;
        sort($genres);
        sort($platforms);

        return [
            'title' => $game->title,
            'slug' => $game->slug,
            'description' => $game->description ?? '',
            'cover_image' => $game->cover_image ?? '',
            'developer' => $game->developer ?? '',
            'publisher' => $game->publisher ?? '',
            'genres' => $genres,
            'platforms' => $platforms,
            'release_date' => $game->release_date?->format('Y-m-d') ?? '',
            'release_status' => $game->release_status->value,
        ];
    }
}
