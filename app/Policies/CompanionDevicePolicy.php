<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanionDevice;
use App\Models\User;

final class CompanionDevicePolicy
{
    public function update(User $user, CompanionDevice $device): bool
    {
        return $device->user_id === $user->id && ! $device->isRevoked();
    }

    public function delete(User $user, CompanionDevice $device): bool
    {
        return $device->user_id === $user->id;
    }
}
