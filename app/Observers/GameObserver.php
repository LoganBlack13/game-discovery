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

    public function created(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "updated" event.
     */
    public function updated(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "deleted" event.
     */
    public function deleted(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "restored" event.
     */
    public function restored(Game $game): void
    {
        //
    }

    /**
     * Handle the Game "force deleted" event.
     */
    public function forceDeleted(Game $game): void
    {
        //
    }
}
