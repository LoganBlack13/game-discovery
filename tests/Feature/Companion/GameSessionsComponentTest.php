<?php

declare(strict_types=1);

use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\TrackedGame;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->device = CompanionDevice::factory()->for($this->user)->create(['label' => 'Bureau']);
    $this->game = Game::factory()->create();
});

function sessionFor(mixed $test, array $attributes = []): GameSession
{
    return GameSession::factory()->for($test->game)->ended()->create([
        'companion_device_id' => $test->device->id,
        ...$attributes,
    ]);
}

test('the block is hidden without device and without session', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertDontSee('Measured playtime');
});

test('a connected device without session shows the empty state', function (): void {
    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertSee('Measured playtime')
        ->assertSee('0 min')
        ->assertSee('No session recorded yet')
        ->assertSee('Don’t track this game with Companion');
});

test('measured playtime, sessions and declared playtime are shown separately', function (): void {
    TrackedGame::factory()->for($this->user)->create(['game_id' => $this->game->id, 'playtime_hours' => 40]);
    sessionFor($this, ['started_at' => now()->subDays(2), 'last_heartbeat_at' => now()->subDays(2)->addHour(), 'active_seconds' => 3600]);
    sessionFor($this, ['started_at' => now()->subDay(), 'last_heartbeat_at' => now()->subDay()->addMinutes(30), 'active_seconds' => 300]);
    GameSession::factory()->for($this->game)->create(['active_seconds' => 9000]);

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertSeeInOrder(['Measured playtime', '1 h 05', '2 sessions', 'Declared playtime', '40 h', 'Last session', '5 min', '1 day ago'])
        ->assertSee('Bureau')
        ->assertDontSee('2 h 30');
});

test('an open session with a recent heartbeat is shown as playing now', function (): void {
    sessionFor($this, ['ended_at' => null, 'end_reason' => null, 'started_at' => now()->subMinutes(42), 'last_heartbeat_at' => now()->subMinute(), 'active_seconds' => 2520]);

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertSee('Playing now · 42 min');
});

test('an open session without recent heartbeat is not playing now', function (): void {
    sessionFor($this, ['ended_at' => null, 'end_reason' => null, 'last_heartbeat_at' => now()->subMinutes(5)]);

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertDontSee('Playing now');
});

test('only the ten latest sessions are listed until asked', function (): void {
    foreach (range(1, 12) as $day) {
        sessionFor($this, ['started_at' => now()->subDays($day), 'last_heartbeat_at' => now()->subDays($day)->addMinutes(10), 'active_seconds' => 600]);
    }

    $component = Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertSee('Show all 12 sessions');
    expect($component->instance()->sessions)->toHaveCount(10);

    $component->call('showAllSessions')->assertDontSee('Show all 12 sessions');
    expect($component->instance()->sessions)->toHaveCount(12);
});

test('a session can be deleted', function (): void {
    $session = sessionFor($this);

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->call('deleteSession', $session->id)
        ->assertSee('No session recorded yet');

    expect($session->fresh())->toBeNull();
});

test('the session of another user cannot be deleted', function (): void {
    $session = GameSession::factory()->for($this->game)->create();

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->call('deleteSession', $session->id)
        ->assertForbidden();

    expect($session->fresh())->not->toBeNull();
});

test('a game can be excluded from tracking and included again', function (): void {
    $component = Livewire::actingAs($this->user)->test('game-sessions', ['game' => $this->game]);

    $component->call('toggleExclusion');
    expect($this->user->companionExcludedGames()->pluck('games.id')->all())->toBe([$this->game->id]);

    $component->call('toggleExclusion');
    expect($this->user->companionExcludedGames()->count())->toBe(0);
});

test('the exclusion toggle needs a connected device', function (): void {
    $this->device->revoke();
    sessionFor($this);

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->assertSee('Measured playtime')
        ->assertDontSee('Don’t track this game with Companion');
});

test('the game page shows the sessions block', function (): void {
    $this->actingAs($this->user)
        ->get(route('games.show', $this->game))
        ->assertSuccessful()
        ->assertSee('Sessions')
        ->assertSee('Measured playtime');
});

test('durations are human readable', function (int $seconds, string $expected): void {
    expect(GameSession::humanDuration($seconds))->toBe($expected);
})->with([
    [0, '0 min'],
    [59, '0 min'],
    [2520, '42 min'],
    [3600, '1 h 00'],
    [3900, '1 h 05'],
    [-30, '0 min'],
]);
