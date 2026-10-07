<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a play session comes from. Each source keeps its provenance (fiche A, R-04).
 */
enum GameSessionSource: string
{
    case QuestlogCompanion = 'questlog_companion';
}
