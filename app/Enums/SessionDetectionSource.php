<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How the Companion recognised the game: a launcher manifest or a plain process match.
 */
enum SessionDetectionSource: string
{
    case Process = 'process';
    case Epic = 'epic';
    case Gog = 'gog';
    case Xbox = 'xbox';
    case ManualMapping = 'manual_mapping';
}
