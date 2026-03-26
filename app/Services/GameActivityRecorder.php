<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameActivityType;
use App\Enums\ReleaseStatus;
use App\Models\Game;
use Carbon\CarbonInterface;

final class GameActivityRecorder
{
    public function recordReleaseChanges(
        Game $game,
        ?CarbonInterface $oldReleaseDate,
        ReleaseStatus|string|null $oldReleaseStatus,
        bool $wasExisting
    ): void {
        $newReleaseDate = $game->release_date;
        $newReleaseStatus = $game->release_status;
        $wasReleased = $this->isReleased($oldReleaseDate, $oldReleaseStatus);
        $isNowReleased = $this->isReleased($newReleaseDate, $newReleaseStatus);

        if ($oldReleaseDate instanceof CarbonInterface && $newReleaseDate !== null
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

        if (! $oldReleaseDate instanceof CarbonInterface && $newReleaseDate !== null) {
            $game->activities()->create([
                'type' => GameActivityType::ReleaseDateAnnounced,
                'title' => 'Release date announced',
                'description' => $newReleaseDate->format('M j, Y'),
                'url' => null,
                'occurred_at' => now(),
            ]);
        }

        if ($wasExisting && ! $wasReleased && $isNowReleased) {
            $game->activities()->create([
                'type' => GameActivityType::GameReleased,
                'title' => $game->title.' released',
                'description' => $newReleaseDate?->format('M j, Y') ?? 'Released',
                'url' => null,
                'occurred_at' => now(),
            ]);
        }
    }

    private function isReleased(?CarbonInterface $releaseDate, ReleaseStatus|string|null $releaseStatus): bool
    {
        $status = $releaseStatus instanceof ReleaseStatus
            ? $releaseStatus
            : ($releaseStatus !== null ? ReleaseStatus::tryFrom($releaseStatus) : null);

        if ($releaseDate instanceof CarbonInterface && $releaseDate->isPast()) {
            return true;
        }

        return $status === ReleaseStatus::Released;
    }
}
