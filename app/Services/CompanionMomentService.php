<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GenerateMomentThumbnail;
use App\Models\CompanionDevice;
use App\Models\GameSession;
use App\Models\SavedMoment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Stores the moments saved with the Companion shortcut (fiche A6) on a private disk, within a per-account quota.
 */
final readonly class CompanionMomentService
{
    public const string DISK = 'local';

    public const int MAX_IMAGE_KILOBYTES = 10 * 1024;

    public const int QUOTA_BYTES = 1024 * 1024 * 1024;

    /**
     * Stores a moment, or returns the stored one when the Companion sends it again.
     *
     * @param  array{uuid: string, session_uuid: string, game_id: int, captured_at: string, client_sent_at: string}  $data
     * @return array{moment: SavedMoment, created: bool}
     */
    public function store(CompanionDevice $device, array $data, UploadedFile $image): array
    {
        $user = $device->user;
        assert($user instanceof User);

        $existing = SavedMoment::query()->find($data['uuid']);
        if ($existing !== null) {
            if ($existing->user_id !== $user->id) {
                throw $this->conflict('moment_conflict', 'This moment identifier is already used.', 409);
            }

            return ['moment' => $existing, 'created' => false];
        }

        $session = $user->gameSessions()->find($data['session_uuid']);
        if (! $session instanceof GameSession) {
            throw $this->conflict('session_unknown', 'Send the session before its moments.', 409);
        }

        if ($session->game_id !== $data['game_id']) {
            throw ValidationException::withMessages(['game_id' => 'The moment does not belong to the game of its session.']);
        }

        $size = (int) $image->getSize();
        if ($this->usedBytes($user) + $size > self::QUOTA_BYTES) {
            throw $this->conflict('quota_exceeded', 'Your moments use all the storage available to your account.', 413);
        }

        $dimensions = getimagesize($image->path());
        if ($dimensions === false) {
            throw ValidationException::withMessages(['image' => 'The image cannot be read.']);
        }

        $extension = $dimensions[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
        $path = $image->storeAs("moments/{$user->id}", "{$data['uuid']}.{$extension}", self::DISK);
        assert(is_string($path));

        $skew = CompanionSessionService::clockSkewSeconds($data['client_sent_at']);
        $moment = SavedMoment::query()->createOrFirst(['id' => $data['uuid']], [
            'user_id' => $user->id,
            'game_id' => $session->game_id,
            'game_session_id' => $session->id,
            'companion_device_id' => $device->id,
            'captured_at' => CarbonImmutable::parse($data['captured_at'])->utc()->addSeconds($skew),
            'disk' => self::DISK,
            'path' => $path,
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'size_bytes' => $size,
        ]);

        if ($moment->wasRecentlyCreated) {
            GenerateMomentThumbnail::dispatch($moment);
        }

        return ['moment' => $moment, 'created' => $moment->wasRecentlyCreated];
    }

    public function usedBytes(User $user): int
    {
        return (int) $user->savedMoments()->sum('size_bytes');
    }

    /**
     * Deletes the moment with its image and thumbnail (fiche A6, F-05).
     */
    public function delete(SavedMoment $moment): void
    {
        Storage::disk($moment->disk)->delete(array_values(array_filter([$moment->path, $moment->thumbnail_path])));
        $moment->delete();
    }

    public function deleteForSession(GameSession $session): void
    {
        $session->moments()->each(fn (SavedMoment $moment) => $this->delete($moment));
    }

    public function deleteAllFor(User $user): void
    {
        Storage::disk(self::DISK)->deleteDirectory("moments/{$user->id}");
        $user->savedMoments()->delete();
    }

    private function conflict(string $code, string $message, int $status): HttpResponseException
    {
        return new HttpResponseException(response()->json(['message' => $message, 'code' => $code], $status));
    }
}
