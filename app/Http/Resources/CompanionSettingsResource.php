<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Services\CompanionMomentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Preferences the Companion applies locally (fiches A3, A5 and A6).
 *
 * @property-read User $resource
 */
final class CompanionSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'tracking_enabled' => $this->resource->companion_tracking_enabled,
            'excluded_game_ids' => $this->resource->companionExcludedGames()->orderBy('games.id')->pluck('games.id')->all(),
            'suggest_unknown_games' => $this->resource->companion_suggest_unknown_games,
            'pending_candidates' => $this->resource->companionMappingCandidates()->awaitingUser()->count(),
            'moment_hotkey' => $this->resource->companion_moment_hotkey,
            'moment_sound' => $this->resource->companion_moment_sound,
            'moment_upload' => $this->resource->companion_moment_upload,
            'moments_quota_reached' => app(CompanionMomentService::class)->usedBytes($this->resource) >= CompanionMomentService::QUOTA_BYTES,
        ];
    }
}
