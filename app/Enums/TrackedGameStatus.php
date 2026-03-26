<?php

declare(strict_types=1);

namespace App\Enums;

enum TrackedGameStatus: string
{
    case ToPlay = 'to_play';
    case Playing = 'playing';
    case Completed = 'completed';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::ToPlay => 'To Play',
            self::Playing => 'Playing',
            self::Completed => 'Completed',
            self::Dropped => 'Dropped',
        };
    }
}
