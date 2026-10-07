<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SavedMoment;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Builds the gallery thumbnail of a moment (fiche A6) with GD: 480 px on the long side, JPEG.
 *
 * Fails when the image cannot be found, so a disk the queue worker cannot reach shows up in the failed jobs.
 */
final class GenerateMomentThumbnail implements ShouldQueue
{
    use Queueable;

    public const int SIZE = 480;

    public const int QUALITY = 80;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public SavedMoment $moment) {}

    public function handle(): void
    {
        $disk = Storage::disk($this->moment->disk);
        $contents = $disk->get($this->moment->path);
        if ($contents === null) {
            throw new RuntimeException("Moment image [{$this->moment->path}] is missing from disk [{$this->moment->disk}].");
        }

        if (getimagesizefromstring($contents) === false) {
            return;
        }

        $source = imagecreatefromstring($contents);
        assert($source instanceof GdImage);

        $scale = min(1, self::SIZE / max(imagesx($source), imagesy($source)));
        $thumbnail = imagescale($source, max(1, (int) round(imagesx($source) * $scale)), max(1, (int) round(imagesy($source) * $scale)), IMG_BICUBIC);
        assert($thumbnail instanceof GdImage);

        ob_start();
        imagejpeg($thumbnail, null, self::QUALITY);
        $jpeg = (string) ob_get_clean();

        $path = (string) preg_replace('/\.\w+$/', '', $this->moment->path).'_thumb.jpg';
        $disk->put($path, $jpeg);
        $this->moment->forceFill(['thumbnail_path' => $path])->save();
    }
}
