<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Enums\CompanionPairingState;
use App\Http\Requests\Companion\ClaimCompanionTokenRequest;
use App\Http\Requests\Companion\StartCompanionPairingRequest;
use App\Http\Resources\CompanionPairingResource;
use App\Models\CompanionPairing;
use App\Services\CompanionPairingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final readonly class PairingController
{
    public function __construct(private CompanionPairingService $pairings) {}

    public function store(StartCompanionPairingRequest $request): JsonResponse
    {
        /** @var array{label: string, platform: string, app_version?: string|null} $validated */
        $validated = $request->validated();

        ['pairing' => $pairing, 'secret' => $secret] = $this->pairings->start(
            $validated['label'],
            $validated['platform'],
            $validated['app_version'] ?? null,
        );

        return new CompanionPairingResource($pairing, $secret)->response()->setStatusCode(201);
    }

    /**
     * Polled by the Companion: 202 while the user has not confirmed, the token once, then 410.
     */
    public function token(ClaimCompanionTokenRequest $request, CompanionPairing $pairing): JsonResponse|Response
    {
        abort_unless($pairing->secretMatches($request->string('pairing_secret')->toString()), 403);

        if ($pairing->state() === CompanionPairingState::Pending) {
            return response()->noContent(202);
        }

        $claimed = $this->pairings->claim($pairing);

        abort_if($claimed === null, 410, 'This pairing expired or was already used.');

        return response()->json([
            'data' => [
                'token' => $claimed['token'],
                'device_id' => $claimed['device']->id,
            ],
        ]);
    }
}
