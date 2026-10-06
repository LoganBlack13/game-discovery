<?php

declare(strict_types=1);

namespace App\Enums;

enum TrackedGameStatus: string
{
    case Watching = 'watching';
    case ToPlay = 'to_play';
    case Playing = 'playing';
    case Paused = 'paused';
    case Completed = 'completed';
    case Mastered = 'mastered';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Watching => 'Watching',
            self::ToPlay => 'To Play',
            self::Playing => 'Playing',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Mastered => '100% Completed',
            self::Dropped => 'Dropped',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Watching => 'badge-ghost',
            self::ToPlay => 'badge-info',
            self::Playing => 'badge-primary',
            self::Paused => 'badge-warning',
            self::Completed, self::Mastered => 'badge-success',
            self::Dropped => 'badge-error',
        };
    }

    /**
     * Statuses that close the personal journey of a game (end date + closing review).
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Mastered, self::Dropped], true);
    }

    /**
     * Statuses that interrupt a game and accept an optional reason.
     */
    public function isInterruption(): bool
    {
        return in_array($this, [self::Paused, self::Dropped], true);
    }
}
