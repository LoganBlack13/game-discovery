<?php

declare(strict_types=1);

namespace App\Enums;

enum GameLauncher: string
{
    case Epic = 'epic';
    case Gog = 'gog';
    case Xbox = 'xbox';
}
