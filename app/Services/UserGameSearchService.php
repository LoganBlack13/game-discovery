<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * User-facing game search with a strict result cap and tracking state.
 */
final class UserGameSearchService
{
    /**
     * Search games by title and return at most $limit results with tracking state for the user.
     *
     * @return Collection<int, UserGameSearchResult>
     */
    public function search(?User $user, string $query, int $limit = 10): Collection
    {
        $term = mb_trim($query);
        if ($term === '') {
            /** @var Collection<int, UserGameSearchResult> $empty */
            $empty = collect();

            return $empty;
        }

        $games = Game::query()
            ->searchByTitle($term)
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($games->isEmpty()) {
            /** @var Collection<int, UserGameSearchResult> $empty */
            $empty = collect();

            return $empty;
        }

        /** @var array<int> $gameIds */
        $gameIds = $games->pluck('id')->all();
        $trackedIds = $this->trackedGameIdsForUser($user, $gameIds);

        /** @var Collection<int, UserGameSearchResult> $result */
        $result = $games->map(fn (Game $game): UserGameSearchResult => new UserGameSearchResult(
            game: $game,
            isTracked: in_array($game->id, $trackedIds, true),
        ))->values();

        return $result;
    }

    /**
     * @param  array<int>  $gameIds
     * @return array<int>
     */
    private function trackedGameIdsForUser(?User $user, array $gameIds): array
    {
        if (! $user instanceof User || $gameIds === []) {
            return [];
        }

        /** @var array<int> $ids */
        $ids = $user->trackedGames()
            ->whereIn('game_id', $gameIds)
            ->pluck('game_id')
            ->all();

        return $ids;
    }
}
