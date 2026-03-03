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
            return collect();
        }

        $games = Game::query()
            ->where('title', 'like', '%'.$term.'%')
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($games->isEmpty()) {
            return collect();
        }

        $trackedIds = $this->trackedGameIdsForUser($user, $games->pluck('id')->all());

        return $games->map(fn (Game $game): UserGameSearchResult => new UserGameSearchResult(
            game: $game,
            isTracked: in_array($game->id, $trackedIds, true),
        ));
    }

    /**
     * @param  array<int>  $gameIds
     * @return array<int>
     */
    private function trackedGameIdsForUser(?User $user, array $gameIds): array
    {
        if ($user === null || $gameIds === []) {
            return [];
        }

        return $user->trackedGames()
            ->whereIn('game_id', $gameIds)
            ->pluck('game_id')
            ->all();
    }
}
