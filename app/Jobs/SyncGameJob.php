<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\GameDataProvider;
use App\Models\Game;
use App\Services\GameActivityRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncGameJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $externalId,
        public ?int $gameId = null
    ) {}

    public function handle(GameDataProvider $provider, GameActivityRecorder $recorder): void
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

        $recorder->recordReleaseChanges(
            $game,
            $oldReleaseDate,
            $oldReleaseStatus,
            $existing !== null
        );
    }
}
