<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Resources\CompanionSettingsResource;
use App\Models\User;
use Illuminate\Http\Request;

final class SettingsController
{
    public function __invoke(Request $request): CompanionSettingsResource
    {
        $user = $request->user();
        assert($user instanceof User);

        return new CompanionSettingsResource($user);
    }
}
