<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CompanionMappingCandidate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read CompanionMappingCandidate $resource
 */
final class CompanionMappingCandidateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'fingerprint' => $this->resource->fingerprint,
            'status' => $this->resource->status->value,
            'mapping_id' => $this->resource->game_executable_mapping_id,
            'proposed_game_id' => $this->resource->proposed_game_id,
        ];
    }
}
