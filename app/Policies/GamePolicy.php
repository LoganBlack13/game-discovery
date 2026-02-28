<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

final class GamePolicy
{
    public function track(?User $user, Game $game): bool
    {
        return $user !== null;
    }

    public function untrack(?User $user, Game $game): bool
    {
        return $user !== null;
    }

    public function update(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }
}
