<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Resources\GameExecutableMappingResource;
use App\Models\GameExecutableMapping;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MappingController
{
    /**
     * Mappings the Companion uses to recognise games: the user's own plus the shared ones, a personal mapping
     * replacing a shared mapping of the same executable (fiche A2, R-09).
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        assert($user instanceof User);

        $mappings = GameExecutableMapping::query()
            ->availableTo($user)
            ->with('game:id,title')
            ->get()
            ->sortBy(fn (GameExecutableMapping $mapping): int => $mapping->user_id === null ? 1 : 0)
            ->unique(fn (GameExecutableMapping $mapping): string => $mapping->matchKey())
            ->sortBy('id')
            ->values();

        return GameExecutableMappingResource::collection($mappings);
    }
}
