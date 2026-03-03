<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;

final readonly class UserGameSearchResult
{
    public function __construct(
        public Game $game,
        public bool $isTracked,
    ) {}
}
