<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class NewsEnrichmentProgressController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'run_id' => ['required', 'string'],
        ]);

        $runId = $validated['run_id'];
        $key = "news_enrichment:progress:{$runId}";
        $progress = Cache::get($key);

        if ($progress === null) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($progress);
    }
}
