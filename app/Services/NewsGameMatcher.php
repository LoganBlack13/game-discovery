<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;

final class NewsGameMatcher
{
    public function findMatchingGame(string $newsTitle): ?Game
    {
        $newsTitle = mb_trim($newsTitle);
        if ($newsTitle === '') {
            return null;
        }

        $games = Game::query()
            ->get(['id', 'title'])
            ->sortByDesc(fn (Game $g): int => mb_strlen($g->title))
            ->values();

        foreach ($games as $game) {
            if ($game->title !== '' && mb_stripos($newsTitle, $game->title) !== false) {
                return $game;
            }
        }

        return null;
    }
}
