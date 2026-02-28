<?php

declare(strict_types=1);

namespace App\Enums;

enum GameActivityType: string
{
    case ReleaseDateChanged = 'release_date_changed';

    case ReleaseDateAnnounced = 'release_date_announced';

    case GameReleased = 'game_released';

    case MajorUpdate = 'major_update';
}
