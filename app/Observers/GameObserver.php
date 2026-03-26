<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Game;
use Illuminate\Support\Str;

final class GameObserver
{
    public function saving(Game $game): void
    {
        if ($game->isDirty('title')) {
            $game->slug = Str::slug($game->title);
        }
    }
}
