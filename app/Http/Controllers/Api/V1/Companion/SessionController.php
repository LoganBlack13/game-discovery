<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Requests\Companion\UpsertGameSessionRequest;
use App\Http\Resources\GameSessionResource;
use App\Models\CompanionDevice;
use App\Services\CompanionSessionService;
use Illuminate\Http\JsonResponse;

final readonly class SessionController
{
    public function __construct(private CompanionSessionService $sessions) {}

    /**
     * Creates or updates a session from a full snapshot: 201 the first time, 200 afterwards.
     */
    public function update(UpsertGameSessionRequest $request, string $session): JsonResponse
    {
        $device = $request->attributes->get(CompanionDevice::class);
        assert($device instanceof CompanionDevice);

        /** @var array{game_id: int, started_at: string, last_heartbeat_at: string, ended_at?: string|null, active_seconds: int, idle_seconds: int, end_reason?: string|null, detection_source: string, mapping_id?: int|null, client_sent_at: string} $snapshot */
        $snapshot = $request->validated();

        $result = $this->sessions->record($device, $session, $snapshot);

        return new GameSessionResource($result['session'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }
}
