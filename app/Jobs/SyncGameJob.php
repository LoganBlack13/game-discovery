<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\GameDataProvider;
use App\Enums\GameActivityType;
use App\Enums\ReleaseStatus;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncGameJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $externalId,
        public ?int $gameId = null
    ) {}

    public function handle(GameDataProvider $provider): void
    {
        $details = $provider->getGameDetails($this->externalId);

        $existing = Game::query()
            ->where('external_source', $details['external_source'])
            ->where('external_id', $details['external_id'])
            ->first();

        $oldReleaseDate = $existing?->release_date;
        $oldReleaseStatus = $existing?->release_status;

        $game = Game::updateOrCreate(
            [
                'external_source' => $details['external_source'],
                'external_id' => $details['external_id'],
            ],
            [
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
            ]
        );

        $newReleaseDate = $game->release_date;
        $newReleaseStatus = $game->release_status;
        $wasReleased = $this->isReleased($oldReleaseDate, $oldReleaseStatus);
        $isNowReleased = $this->isReleased($newReleaseDate, $newReleaseStatus);

        if ($oldReleaseDate !== null && $newReleaseDate !== null
            && $oldReleaseDate->format('Y-m-d') !== $newReleaseDate->format('Y-m-d')) {
            $game->activities()->create([
                'type' => GameActivityType::ReleaseDateChanged,
                'title' => 'Release date changed',
                'description' => sprintf(
                    'From %s to %s',
                    $oldReleaseDate->format('M j, Y'),
                    $newReleaseDate->format('M j, Y')
                ),
                'url' => null,
                'occurred_at' => now(),
            ]);
        }

        if ($oldReleaseDate === null && $newReleaseDate !== null) {
            $game->activities()->create([
                'type' => GameActivityType::ReleaseDateAnnounced,
                'title' => 'Release date announced',
                'description' => $newReleaseDate->format('M j, Y'),
                'url' => null,
                'occurred_at' => now(),
            ]);
        }

        if ($existing !== null && ! $wasReleased && $isNowReleased) {
            $game->activities()->create([
                'type' => GameActivityType::GameReleased,
                'title' => $game->title.' released',
                'description' => $newReleaseDate?->format('M j, Y') ?? 'Released',
                'url' => null,
                'occurred_at' => now(),
            ]);
        }
    }

    private function isReleased(?\Carbon\CarbonInterface $releaseDate, ReleaseStatus|string|null $releaseStatus): bool
    {
        $status = $releaseStatus instanceof ReleaseStatus
            ? $releaseStatus
            : ($releaseStatus !== null ? ReleaseStatus::tryFrom((string) $releaseStatus) : null);

        if ($releaseDate !== null && $releaseDate->isPast()) {
            return true;
        }

        return $status === ReleaseStatus::Released;
    }
}
