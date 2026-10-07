<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CompanionDevice;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts only Companion device tokens whose device is still active, and exposes the device to controllers.
 */
final class EnsureCompanionDevice
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->can(CompanionDevice::TOKEN_ABILITY)) {
            throw new AuthenticationException;
        }

        $device = CompanionDevice::query()
            ->active()
            ->where('personal_access_token_id', $token->getKey())
            ->first();

        if ($device === null) {
            throw new AuthenticationException;
        }

        $device->markSeen();
        $request->attributes->set(CompanionDevice::class, $device);

        return $next($request);
    }
}
