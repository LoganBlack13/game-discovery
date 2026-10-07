<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read GameSession $resource
 */
final class GameSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'game_id' => $this->resource->game_id,
            'started_at' => $this->resource->started_at->toIso8601ZuluString(),
            'last_heartbeat_at' => $this->resource->last_heartbeat_at->toIso8601ZuluString(),
            'ended_at' => $this->resource->ended_at?->toIso8601ZuluString(),
            'active_seconds' => $this->resource->active_seconds,
            'idle_seconds' => $this->resource->idle_seconds,
            'end_reason' => $this->resource->end_reason?->value,
            'detection_source' => $this->resource->detection_source->value,
        ];
    }
}
