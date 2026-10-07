<?php

declare(strict_types=1);

use App\Enums\GameSessionSource;
use App\Enums\SessionEndReason;
use App\Enums\TrackedGameStatus;
use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\GameSession;
use App\Models\TrackedGame;
use App\Models\User;

const SESSION_ID = '0199b6d4-7a3e-7c1f-9d2e-4b5a6c7d8e9f';

beforeEach(function (): void {
    $this->device = CompanionDevice::factory()->create();
    $this->token = companionToken($this->device);
    $this->game = Game::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function snapshot(Game $game, array $overrides = []): array
{
    return [
        'game_id' => $game->id,
        'started_at' => now()->subMinutes(30)->toIso8601ZuluString(),
        'last_heartbeat_at' => now()->subMinute()->toIso8601ZuluString(),
        'ended_at' => null,
        'active_seconds' => 1500,
        'idle_seconds' => 0,
        'end_reason' => null,
        'detection_source' => 'gog',
        'mapping_id' => null,
        'client_sent_at' => now()->toIso8601ZuluString(),
        ...$overrides,
    ];
}

function putSnapshot(mixed $test, array $payload, string $sessionId = SESSION_ID): Illuminate\Testing\TestResponse
{
    return $test->withToken($test->token)->putJson(route('api.v1.companion.sessions.update', $sessionId), $payload);
}

test('a first snapshot creates the session', function (): void {
    putSnapshot($this, snapshot($this->game))
        ->assertCreated()
        ->assertJsonPath('data.id', SESSION_ID)
        ->assertJsonPath('data.active_seconds', 1500)
        ->assertJsonPath('data.ended_at', null)
        ->assertJsonPath('data.detection_source', 'gog');

    $session = GameSession::query()->findOrFail(SESSION_ID);
    expect($session->source)->toBe(GameSessionSource::QuestlogCompanion)
        ->and($session->companion_device_id)->toBe($this->device->id)
        ->and($session->user_id)->toBe($this->device->user_id)
        ->and($session->started_at->toDateTimeString())->toBe(now()->subMinutes(30)->toDateTimeString())
        ->and($session->effects_applied_at)->not->toBeNull();
});

test('replaying a snapshot creates one session and one status change', function (): void {
    putSnapshot($this, snapshot($this->game))->assertCreated();
    putSnapshot($this, snapshot($this->game))->assertSuccessful()->assertJsonPath('data.active_seconds', 1500);
    putSnapshot($this, snapshot($this->game))->assertOk();

    $entry = TrackedGame::query()->where('user_id', $this->device->user_id)->sole();
    expect(GameSession::query()->count())->toBe(1)
        ->and($entry->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->statusChanges()->reorder('id')->pluck('to_status')->all())->toBe([TrackedGameStatus::Watching, TrackedGameStatus::Playing]);
});

test('newer snapshots move the session forward', function (): void {
    putSnapshot($this, snapshot($this->game));

    putSnapshot($this, snapshot($this->game, [
        'last_heartbeat_at' => now()->toIso8601ZuluString(),
        'active_seconds' => 1560,
        'idle_seconds' => 30,
    ]))->assertOk()->assertJsonPath('data.active_seconds', 1560)->assertJsonPath('data.idle_seconds', 30);
});

test('an older snapshot never moves the session back', function (): void {
    putSnapshot($this, snapshot($this->game, ['active_seconds' => 1700, 'idle_seconds' => 20]));

    putSnapshot($this, snapshot($this->game, [
        'last_heartbeat_at' => now()->subMinutes(5)->toIso8601ZuluString(),
        'active_seconds' => 1200,
        'idle_seconds' => 0,
    ]))->assertOk();

    $session = GameSession::query()->findOrFail(SESSION_ID);
    expect($session->active_seconds)->toBe(1700)
        ->and($session->idle_seconds)->toBe(20)
        ->and($session->last_heartbeat_at->toDateTimeString())->toBe(now()->subMinute()->toDateTimeString());
});

test('a session closed by its device is final', function (): void {
    putSnapshot($this, snapshot($this->game, [
        'ended_at' => now()->subMinute()->toIso8601ZuluString(),
        'end_reason' => 'closed',
    ]))->assertCreated()->assertJsonPath('data.end_reason', 'closed');

    putSnapshot($this, snapshot($this->game, [
        'last_heartbeat_at' => now()->toIso8601ZuluString(),
        'active_seconds' => 1700,
    ]))->assertOk()->assertJsonPath('data.active_seconds', 1500)->assertJsonPath('data.end_reason', 'closed');
});

test('a session closed after fifteen minutes of silence is closed at its last heartbeat', function (): void {
    putSnapshot($this, snapshot($this->game));

    $this->travel(10)->minutes();
    $this->artisan('companion:close-stale-sessions')->expectsOutputToContain('Closed 0 stale session(s).');

    $this->travel(10)->minutes();
    $this->artisan('companion:close-stale-sessions')->expectsOutputToContain('Closed 1 stale session(s).')->assertSuccessful();

    $session = GameSession::query()->findOrFail(SESSION_ID);
    expect($session->end_reason)->toBe(SessionEndReason::Timeout)
        ->and($session->ended_at?->equalTo($session->last_heartbeat_at))->toBeTrue();
});

test('the device can still complete a session closed by timeout', function (): void {
    GameSession::factory()->for($this->game)->ended(SessionEndReason::Timeout)->create([
        'id' => SESSION_ID,
        'companion_device_id' => $this->device->id,
        'started_at' => now()->subMinutes(30),
        'last_heartbeat_at' => now()->subMinutes(20),
        'active_seconds' => 600,
    ]);

    putSnapshot($this, snapshot($this->game, [
        'ended_at' => now()->subMinute()->toIso8601ZuluString(),
        'end_reason' => 'recovered',
    ]))->assertOk()->assertJsonPath('data.end_reason', 'recovered')->assertJsonPath('data.active_seconds', 1500);
});

test('a heartbeat after a timeout reopens the session', function (): void {
    GameSession::factory()->for($this->game)->ended(SessionEndReason::Timeout)->create([
        'id' => SESSION_ID,
        'companion_device_id' => $this->device->id,
        'started_at' => now()->subMinutes(30),
        'last_heartbeat_at' => now()->subMinutes(20),
    ]);

    putSnapshot($this, snapshot($this->game))->assertOk()->assertJsonPath('data.ended_at', null)->assertJsonPath('data.end_reason', null);
});

test('a new session supersedes the open session of the same game on the same device', function (): void {
    $previous = GameSession::factory()->for($this->game)->create(['companion_device_id' => $this->device->id]);
    $otherGame = GameSession::factory()->create(['companion_device_id' => $this->device->id]);
    $otherDevice = GameSession::factory()->for($this->game)->create();

    putSnapshot($this, snapshot($this->game))->assertCreated();

    expect($previous->fresh()?->end_reason)->toBe(SessionEndReason::Superseded)
        ->and($previous->fresh()?->ended_at?->equalTo($previous->last_heartbeat_at))->toBeTrue()
        ->and($otherGame->fresh()?->ended_at)->toBeNull()
        ->and($otherDevice->fresh()?->ended_at)->toBeNull();
});

test('a superseded session can still receive its final snapshot', function (): void {
    GameSession::factory()->for($this->game)->ended(SessionEndReason::Superseded)->create([
        'id' => SESSION_ID,
        'companion_device_id' => $this->device->id,
        'started_at' => now()->subMinutes(30),
    ]);

    putSnapshot($this, snapshot($this->game))->assertOk()->assertJsonPath('data.end_reason', 'superseded');
    putSnapshot($this, snapshot($this->game, ['ended_at' => now()->subMinute()->toIso8601ZuluString(), 'end_reason' => 'closed']))
        ->assertOk()
        ->assertJsonPath('data.end_reason', 'closed');
});

test('the first session updates the personal status', function (?TrackedGameStatus $before, TrackedGameStatus $after): void {
    TrackedGame::factory()->create([
        'user_id' => $this->device->user_id,
        'game_id' => $this->game->id,
        'status' => $before,
        'playtime_hours' => 12,
    ]);

    putSnapshot($this, snapshot($this->game))->assertCreated();

    $entry = TrackedGame::query()->where('user_id', $this->device->user_id)->sole();
    expect($entry->status)->toBe($after)
        ->and($entry->playtime_hours)->toBe(12);
})->with([
    'no status' => [null, TrackedGameStatus::Playing],
    'watching' => [TrackedGameStatus::Watching, TrackedGameStatus::Playing],
    'to play' => [TrackedGameStatus::ToPlay, TrackedGameStatus::Playing],
    'paused' => [TrackedGameStatus::Paused, TrackedGameStatus::Playing],
    'playing' => [TrackedGameStatus::Playing, TrackedGameStatus::Playing],
    'completed' => [TrackedGameStatus::Completed, TrackedGameStatus::Completed],
    '100% completed' => [TrackedGameStatus::Mastered, TrackedGameStatus::Mastered],
    'dropped' => [TrackedGameStatus::Dropped, TrackedGameStatus::Dropped],
]);

test('snapshots move the last activity forward only', function (): void {
    $entry = TrackedGame::factory()->playing()->create([
        'user_id' => $this->device->user_id,
        'game_id' => $this->game->id,
        'last_activity_at' => now()->subDay(),
    ]);
    putSnapshot($this, snapshot($this->game));
    $this->travel(5)->minutes();

    putSnapshot($this, snapshot($this->game, ['last_heartbeat_at' => now()->toIso8601ZuluString()]))->assertOk();
    expect($entry->fresh()?->last_activity_at?->toDateTimeString())->toBe(now()->toDateTimeString());

    putSnapshot($this, snapshot($this->game, ['last_heartbeat_at' => now()->subMinutes(3)->toIso8601ZuluString()]))->assertOk();
    expect($entry->fresh()?->last_activity_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('a device cannot change the session of another device', function (): void {
    GameSession::factory()->for($this->game)->create(['id' => SESSION_ID]);

    putSnapshot($this, snapshot($this->game))->assertForbidden();
});

test('a snapshot for an unknown game is not found', function (): void {
    putSnapshot($this, snapshot($this->game, ['game_id' => 999_999]))->assertNotFound();

    expect(GameSession::query()->count())->toBe(0);
});

test('snapshots are validated', function (array $overrides, string $field): void {
    putSnapshot($this, snapshot($this->game, $overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing game' => [['game_id' => null], 'game_id'],
    'negative active time' => [['active_seconds' => -1], 'active_seconds'],
    'unknown source' => [['detection_source' => 'steam'], 'detection_source'],
    'closed without reason' => [['ended_at' => '2026-10-07T12:00:00Z'], 'end_reason'],
    'server-side reason' => [['ended_at' => '2026-10-07T12:00:00Z', 'end_reason' => 'timeout'], 'end_reason'],
    'missing send time' => [['client_sent_at' => null], 'client_sent_at'],
]);

test('incoherent snapshots are refused', function (Closure $overrides, string $field): void {
    putSnapshot($this, snapshot($this->game, $overrides()))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'starts in the future' => [fn (): array => ['started_at' => now()->addMinutes(10)->toIso8601ZuluString(), 'last_heartbeat_at' => now()->addMinutes(11)->toIso8601ZuluString()], 'started_at'],
    'too old' => [fn (): array => ['started_at' => now()->subDays(31)->toIso8601ZuluString()], 'started_at'],
    'heartbeat before start' => [fn (): array => ['last_heartbeat_at' => now()->subHour()->toIso8601ZuluString()], 'last_heartbeat_at'],
    'longer than 48 hours' => [fn (): array => ['started_at' => now()->subHours(50)->toIso8601ZuluString()], 'ended_at'],
    'more time than the session lasted' => [fn (): array => ['active_seconds' => 1800, 'idle_seconds' => 100], 'active_seconds'],
]);

test('a device clock off by more than two minutes is corrected', function (): void {
    $clientNow = now()->subHour();

    putSnapshot($this, snapshot($this->game, [
        'started_at' => $clientNow->copy()->subMinutes(30)->toIso8601ZuluString(),
        'last_heartbeat_at' => $clientNow->toIso8601ZuluString(),
        'client_sent_at' => $clientNow->toIso8601ZuluString(),
    ]))->assertCreated();

    $session = GameSession::query()->findOrFail(SESSION_ID);
    expect($session->started_at->toDateTimeString())->toBe(now()->subMinutes(30)->toDateTimeString())
        ->and($session->last_heartbeat_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('a small clock difference is not corrected', function (): void {
    putSnapshot($this, snapshot($this->game, ['client_sent_at' => now()->subSeconds(90)->toIso8601ZuluString()]))->assertCreated();

    expect(GameSession::query()->findOrFail(SESSION_ID)->started_at->toDateTimeString())
        ->toBe(now()->subMinutes(30)->toDateTimeString());
});

test('only mappings available to the user are linked', function (): void {
    $shared = GameExecutableMapping::factory()->for($this->game)->create();
    $foreign = GameExecutableMapping::factory()->for($this->game)->create(['user_id' => User::factory()->create()->id]);

    putSnapshot($this, snapshot($this->game, ['mapping_id' => $shared->id]))->assertCreated();
    putSnapshot($this, snapshot($this->game, ['mapping_id' => $foreign->id]), '0199b6d4-7a3e-7c1f-9d2e-000000000001')->assertCreated();

    expect(GameSession::query()->findOrFail(SESSION_ID)->game_executable_mapping_id)->toBe($shared->id)
        ->and(GameSession::query()->findOrFail('0199b6d4-7a3e-7c1f-9d2e-000000000001')->game_executable_mapping_id)->toBeNull();
});

test('sessions require a companion token and a uuid', function (): void {
    $this->putJson(route('api.v1.companion.sessions.update', SESSION_ID), snapshot($this->game))->assertUnauthorized();
    $this->withToken($this->token)->putJson('/api/v1/companion/sessions/not-a-uuid', snapshot($this->game))->assertNotFound();
});
