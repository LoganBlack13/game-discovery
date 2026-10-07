<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameExecutableMapping;
use App\Models\User;

/**
 * Only personal mappings can be changed by their owner; shared mappings are managed by Questlog.
 */
final class GameExecutableMappingPolicy
{
    public function update(User $user, GameExecutableMapping $mapping): bool
    {
        return $mapping->user_id === $user->id;
    }

    public function delete(User $user, GameExecutableMapping $mapping): bool
    {
        return $mapping->user_id === $user->id;
    }
}
