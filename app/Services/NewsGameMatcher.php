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

        $newsWords = $this->titleToWords($newsTitle);
        if ($newsWords === []) {
            return null;
        }

        $games = Game::query()
            ->get(['id', 'title'])
            ->sortByDesc(fn (Game $g): int => mb_strlen($g->title))
            ->values();

        foreach ($games as $game) {
            if ($game->title === '') {
                continue;
            }
            $gameWords = $this->titleToWords($game->title);
            if ($gameWords === []) {
                continue;
            }
            if ($this->allWordsPresent($gameWords, $newsWords)) {
                return $game;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function titleToWords(string $title): array
    {
        $normalized = mb_strtolower(mb_trim($title));
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $w): string => preg_replace('/^\p{P}+|\p{P}+$/u', '', $w),
            $words,
        ), fn (string $w): bool => $w !== ''));
    }

    /**
     * @param  list<string>  $gameWords
     * @param  list<string>  $newsWords
     */
    private function allWordsPresent(array $gameWords, array $newsWords): bool
    {
        $newsSet = array_flip($newsWords);

        foreach ($gameWords as $word) {
            if (! isset($newsSet[$word])) {
                return false;
            }
        }

        return true;
    }
}
