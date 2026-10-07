<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanionMappingCandidate;
use App\Models\User;

final class CompanionMappingCandidatePolicy
{
    public function update(User $user, CompanionMappingCandidate $candidate): bool
    {
        return $candidate->user_id === $user->id;
    }
}
