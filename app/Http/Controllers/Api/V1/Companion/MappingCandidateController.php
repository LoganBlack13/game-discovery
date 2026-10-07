<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Requests\Companion\RecordMappingCandidatesRequest;
use App\Http\Resources\CompanionMappingCandidateResource;
use App\Models\CompanionDevice;
use App\Services\CompanionMappingService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class MappingCandidateController
{
    public function __construct(private CompanionMappingService $mappings) {}

    /**
     * Executables and installed games the Companion could not recognise. Resolved ones come back as `mapped`;
     * the Companion then reloads its mappings.
     */
    public function store(RecordMappingCandidatesRequest $request): AnonymousResourceCollection
    {
        $device = $request->attributes->get(CompanionDevice::class);
        assert($device instanceof CompanionDevice);

        /** @var list<array{fingerprint: string, executable_name: string, path_fragment?: string|null, launcher?: string|null, launcher_game_id?: string|null, display_name?: string|null, product_name?: string|null, total_seconds: int, first_seen_at: string, last_seen_at: string}> $candidates */
        $candidates = $request->validated('candidates');

        return CompanionMappingCandidateResource::collection($this->mappings->record($device, $candidates));
    }
}
