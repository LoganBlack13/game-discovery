<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GameExecutableMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read GameExecutableMapping $resource
 */
final class GameExecutableMappingResource extends JsonResource
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
            'game_title' => $this->resource->game?->title,
            'executable_name' => $this->resource->executable_name,
            'path_fragment' => $this->resource->path_fragment,
            'launcher' => $this->resource->launcher?->value,
            'launcher_game_id' => $this->resource->launcher_game_id,
            'confidence' => $this->resource->confidence->value,
        ];
    }
}
