<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Tracking preferences the Companion applies locally (fiche A3).
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
        ];
    }
}
