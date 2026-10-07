<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SavedMoment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read SavedMoment $resource
 */
final class SavedMomentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->id,
            'session_uuid' => $this->resource->game_session_id,
            'game_id' => $this->resource->game_id,
            'captured_at' => $this->resource->captured_at->toIso8601ZuluString(),
            'size_bytes' => $this->resource->size_bytes,
        ];
    }
}
