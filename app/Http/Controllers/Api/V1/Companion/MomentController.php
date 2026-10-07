<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Companion;

use App\Http\Requests\Companion\StoreSavedMomentRequest;
use App\Http\Resources\SavedMomentResource;
use App\Models\CompanionDevice;
use App\Services\CompanionMomentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final readonly class MomentController
{
    public function __construct(private CompanionMomentService $moments) {}

    /**
     * Stores a moment uploaded by the Companion: 201 the first time, 200 when it is sent again.
     */
    public function store(StoreSavedMomentRequest $request): JsonResponse
    {
        $device = $request->attributes->get(CompanionDevice::class);
        assert($device instanceof CompanionDevice);

        $image = $request->file('image');
        assert($image instanceof UploadedFile);

        $result = $this->moments->store($device, [
            'uuid' => $request->string('uuid')->toString(),
            'session_uuid' => $request->string('session_uuid')->toString(),
            'game_id' => $request->integer('game_id'),
            'captured_at' => $request->string('captured_at')->toString(),
            'client_sent_at' => $request->string('client_sent_at')->toString(),
        ], $image);

        return new SavedMomentResource($result['moment'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }
}
