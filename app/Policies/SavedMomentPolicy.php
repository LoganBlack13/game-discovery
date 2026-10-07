<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SavedMoment;
use App\Models\User;

final class SavedMomentPolicy
{
    public function view(User $user, SavedMoment $moment): bool
    {
        return $moment->user_id === $user->id;
    }

    public function update(User $user, SavedMoment $moment): bool
    {
        return $moment->user_id === $user->id;
    }

    public function delete(User $user, SavedMoment $moment): bool
    {
        return $moment->user_id === $user->id;
    }
}
