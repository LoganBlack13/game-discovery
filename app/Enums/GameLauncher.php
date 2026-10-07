<?php

declare(strict_types=1);

namespace App\Enums;

enum GameLauncher: string
{
    case Epic = 'epic';
    case Gog = 'gog';
    case Xbox = 'xbox';

    /**
     * IGDB `external_game_source` ids of the store behind the launcher.
     *
     * @return list<int>
     */
    public function igdbExternalSources(): array
    {
        return match ($this) {
            self::Gog => [5],
            self::Epic => [26],
            self::Xbox => [11, 31, 54],
        };
    }
}
