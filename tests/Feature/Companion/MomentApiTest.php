<?php

declare(strict_types=1);

use App\Jobs\GenerateMomentThumbnail;
use App\Models\CompanionDevice;
use App\Models\GameSession;
use App\Models\SavedMoment;
use App\Models\User;
use App\Services\CompanionMomentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
    Queue::fake();
    $this->device = CompanionDevice::factory()->create();
    $this->session = GameSession::factory()->create(['companion_device_id' => $this->device->id]);
    $this->token = companionToken($this->device);
});

/**
 * @return array<string, mixed>
 */
function momentPayload(GameSession $session, array $overrides = []): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'session_uuid' => $session->id,
        'game_id' => $session->game_id,
        'captured_at' => now()->subMinute()->toIso8601ZuluString(),
        'client_sent_at' => now()->toIso8601ZuluString(),
        'image' => UploadedFile::fake()->image('moment.jpg', 1920, 1080),
        ...$overrides,
    ];
}

test('a moment is stored privately and its thumbnail is queued', function (): void {
    $payload = momentPayload($this->session);

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), $payload, ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.uuid', $payload['uuid'])
        ->assertJsonPath('data.session_uuid', $this->session->id)
        ->assertJsonPath('data.game_id', $this->session->game_id);

    $moment = SavedMoment::query()->findOrFail($payload['uuid']);
    expect($moment->user_id)->toBe($this->session->user_id)
        ->and($moment->companion_device_id)->toBe($this->device->id)
        ->and($moment->path)->toBe("moments/{$moment->user_id}/{$moment->id}.jpg")
        ->and($moment->width)->toBe(1920)
        ->and($moment->height)->toBe(1080)
        ->and($moment->size_bytes)->toBeGreaterThan(0)
        ->and($moment->game?->is($this->session->game))->toBeTrue()
        ->and($moment->device?->is($this->device))->toBeTrue()
        ->and($moment->session?->is($this->session))->toBeTrue();
    Storage::disk('local')->assertExists($moment->path);
    Queue::assertPushed(GenerateMomentThumbnail::class, fn (GenerateMomentThumbnail $job): bool => $job->moment->is($moment));
});

test('moments are stored on the configured disk', function (): void {
    Storage::fake('s3');
    config(['filesystems.moments' => 's3']);
    $payload = momentPayload($this->session);

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), $payload, ['Accept' => 'application/json'])
        ->assertCreated();

    $moment = SavedMoment::query()->findOrFail($payload['uuid']);
    expect($moment->disk)->toBe('s3');
    Storage::disk('s3')->assertExists($moment->path);
    Storage::disk('local')->assertMissing($moment->path);
});

test('png moments keep their format', function (): void {
    $payload = momentPayload($this->session, ['image' => UploadedFile::fake()->image('moment.png', 800, 600)]);

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), $payload, ['Accept' => 'application/json'])
        ->assertCreated();

    expect(SavedMoment::query()->findOrFail($payload['uuid'])->path)->toEndWith('.png');
});

test('sending a moment again is idempotent', function (): void {
    $payload = momentPayload($this->session);
    $this->withToken($this->token)->post(route('api.v1.companion.moments.store'), $payload, ['Accept' => 'application/json'])->assertCreated();

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), [...$payload, 'image' => UploadedFile::fake()->image('again.jpg', 640, 480)], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.uuid', $payload['uuid']);

    expect(SavedMoment::query()->count())->toBe(1)
        ->and(SavedMoment::query()->findOrFail($payload['uuid'])->width)->toBe(1920);
    Queue::assertPushed(GenerateMomentThumbnail::class, 1);
});

test('a moment identifier used by another account is a conflict', function (): void {
    $other = SavedMoment::factory()->create();

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session, ['uuid' => $other->id]), ['Accept' => 'application/json'])
        ->assertConflict()
        ->assertJsonPath('code', 'moment_conflict');
});

test('a moment of an unknown session is a conflict so the companion retries later', function (): void {
    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session, ['session_uuid' => (string) Str::uuid()]), ['Accept' => 'application/json'])
        ->assertConflict()
        ->assertJsonPath('code', 'session_unknown');

    expect(SavedMoment::query()->count())->toBe(0);
});

test('a moment cannot be attached to the session of another user', function (): void {
    $foreign = GameSession::factory()->create();

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($foreign), ['Accept' => 'application/json'])
        ->assertConflict()
        ->assertJsonPath('code', 'session_unknown');
});

test('the game must be the game of the session', function (): void {
    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session, ['game_id' => $this->session->game_id + 1]), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('game_id');
});

test('the storage quota is enforced', function (): void {
    $user = $this->device->user;
    assert($user instanceof User);
    SavedMoment::factory()->create([
        'game_session_id' => $this->session->id,
        'size_bytes' => CompanionMomentService::QUOTA_BYTES - 10,
    ]);

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session), ['Accept' => 'application/json'])
        ->assertStatus(413)
        ->assertJsonPath('code', 'quota_exceeded');

    $this->withToken($this->token)
        ->getJson(route('api.v1.companion.settings'))
        ->assertJsonPath('data.moments_quota_reached', false);
});

test('the settings report a full storage', function (): void {
    SavedMoment::factory()->create([
        'game_session_id' => $this->session->id,
        'size_bytes' => CompanionMomentService::QUOTA_BYTES,
    ]);

    $this->withToken($this->token)
        ->getJson(route('api.v1.companion.settings'))
        ->assertJsonPath('data.moments_quota_reached', true);
});

test('the capture time is corrected when the device clock is off', function (): void {
    $this->freezeSecond();
    $payload = momentPayload($this->session, [
        'captured_at' => now()->addHour()->subMinute()->toIso8601ZuluString(),
        'client_sent_at' => now()->addHour()->toIso8601ZuluString(),
    ]);

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), $payload, ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.captured_at', now()->subMinute()->toIso8601ZuluString());
});

test('unreadable images are refused', function (): void {
    $fake = UploadedFile::fake()->createWithContent('moment.jpg', "\xFF\xD8\xFF\xE0broken");

    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session, ['image' => $fake]), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

test('invalid moments are refused', function (string $field, mixed $value): void {
    $this->withToken($this->token)
        ->post(route('api.v1.companion.moments.store'), momentPayload($this->session, [$field => $value]), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'uuid' => ['uuid', 'not-a-uuid'],
    'session' => ['session_uuid', ''],
    'game' => ['game_id', 0],
    'capture time' => ['captured_at', 'yesterday-ish'],
    'send time' => ['client_sent_at', ''],
    'missing image' => ['image', null],
    'not an image' => ['image', UploadedFile::fake()->create('notes.txt', 1, 'text/plain')],
    'too large' => ['image', UploadedFile::fake()->image('huge.jpg')->size(CompanionMomentService::MAX_IMAGE_KILOBYTES + 1)],
]);

test('moments require a companion token', function (): void {
    $this->postJson(route('api.v1.companion.moments.store'))->assertUnauthorized();
});
