<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Resources\CompanionMeResource;
use App\Models\CompanionDevice;
use Illuminate\Http\Request;

final class MeController
{
    public function __invoke(Request $request): CompanionMeResource
    {
        $device = $request->attributes->get(CompanionDevice::class);
        assert($device instanceof CompanionDevice);

        return new CompanionMeResource($device->load('user'));
    }
}
