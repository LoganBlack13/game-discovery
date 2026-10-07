<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SavedMoment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves moment images to their owner only, from the private disk (fiche A6, F-04).
 */
final class SavedMomentImageController
{
    public function __invoke(Request $request, SavedMoment $moment): StreamedResponse
    {
        Gate::authorize('view', $moment);

        $path = $request->query('size') === 'thumbnail' && $moment->thumbnail_path !== null
            ? $moment->thumbnail_path
            : $moment->path;

        $disk = Storage::disk($moment->disk);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
