<?php

declare(strict_types=1);

use App\Jobs\GenerateMomentThumbnail;
use App\Models\SavedMoment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

function storedMoment(int $width = 1920, int $height = 1080): SavedMoment
{
    $moment = SavedMoment::factory()->create(['width' => $width, 'height' => $height]);
    Storage::disk('local')->put($moment->path, UploadedFile::fake()->image('m.jpg', $width, $height)->getContent());

    return $moment;
}

test('the owner gets the image and its thumbnail', function (): void {
    $moment = storedMoment();
    $owner = $moment->user;
    assert($owner instanceof User);
    new GenerateMomentThumbnail($moment)->handle();

    $this->actingAs($owner)
        ->get(route('moments.image', $moment))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=86400, private');

    $thumbnail = $this->actingAs($owner)->get(route('moments.image', ['moment' => $moment, 'size' => 'thumbnail']));
    $thumbnail->assertOk();
    $size = getimagesizefromstring($thumbnail->streamedContent());
    expect($size)->not->toBeFalse()
        ->and($size[0] ?? null)->toBe(480)
        ->and($size[1] ?? null)->toBe(270);
});

test('the full image is served while the thumbnail is not ready', function (): void {
    $moment = storedMoment();
    $owner = $moment->user;
    assert($owner instanceof User);

    $this->actingAs($owner)
        ->get(route('moments.image', ['moment' => $moment, 'size' => 'thumbnail']))
        ->assertOk();
});

test('another user cannot see a moment', function (): void {
    $moment = storedMoment();

    $this->actingAs(User::factory()->create())
        ->get(route('moments.image', $moment))
        ->assertForbidden();
});

test('guests are redirected to login', function (): void {
    $this->get(route('moments.image', storedMoment()))->assertRedirect(route('login'));
});

test('a missing file is not found', function (): void {
    $moment = SavedMoment::factory()->create();
    $owner = $moment->user;
    assert($owner instanceof User);

    $this->actingAs($owner)->get(route('moments.image', $moment))->assertNotFound();
});

test('small images keep their size in the thumbnail', function (): void {
    $moment = storedMoment(320, 200);

    new GenerateMomentThumbnail($moment)->handle();

    $moment->refresh();
    expect($moment->thumbnail_path)->toBe(str_replace('.jpg', '_thumb.jpg', $moment->path));
    $size = getimagesizefromstring((string) Storage::disk('local')->get((string) $moment->thumbnail_path));
    expect($size[0] ?? null)->toBe(320)->and($size[1] ?? null)->toBe(200);
});

test('the thumbnail job skips unreadable files', function (): void {
    $broken = SavedMoment::factory()->create();
    Storage::disk('local')->put($broken->path, 'not an image');

    new GenerateMomentThumbnail($broken)->handle();

    expect($broken->fresh()?->thumbnail_path)->toBeNull();
});

test('the thumbnail job fails when the image is missing from its disk', function (): void {
    $missing = SavedMoment::factory()->create();

    expect(fn () => new GenerateMomentThumbnail($missing)->handle())
        ->toThrow(RuntimeException::class, "Moment image [{$missing->path}] is missing from disk [local].");
    expect($missing->fresh()?->thumbnail_path)->toBeNull();
});

test('the thumbnail is written to the disk of the moment', function (): void {
    Storage::fake('s3');
    $moment = SavedMoment::factory()->create(['disk' => 's3']);
    Storage::disk('s3')->put($moment->path, UploadedFile::fake()->image('m.jpg', 1920, 1080)->getContent());

    new GenerateMomentThumbnail($moment)->handle();

    Storage::disk('s3')->assertExists((string) $moment->refresh()->thumbnail_path);
    Storage::disk('local')->assertMissing((string) $moment->thumbnail_path);
});
