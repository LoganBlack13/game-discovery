<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

final class GameSessionPolicy
{
    public function delete(User $user, GameSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
