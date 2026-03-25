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

    /** @codeCoverageIgnore */
    public function created(Game $game): void {}

    /** @codeCoverageIgnore */
    public function updated(Game $game): void {}

    /** @codeCoverageIgnore */
    public function deleted(Game $game): void {}

    /** @codeCoverageIgnore */
    public function restored(Game $game): void {}

    /** @codeCoverageIgnore */
    public function forceDeleted(Game $game): void {}
}
