<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class GameRequestProgressController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, string> $validated */
        $validated = $request->validate([
            'run_id' => ['required', 'string'],
        ]);

        $runId = $validated['run_id'];
        $key = 'game_requests:progress:'.$runId;
        /** @var array<string, mixed>|null $progress */
        $progress = Cache::get($key);

        if ($progress === null) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($progress);
    }
}
