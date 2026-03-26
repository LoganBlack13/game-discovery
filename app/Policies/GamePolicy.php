<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class GamePolicy
{
    public function track(?User $user): bool
    {
        return $user instanceof User;
    }

    public function untrack(?User $user): bool
    {
        return $user instanceof User;
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
