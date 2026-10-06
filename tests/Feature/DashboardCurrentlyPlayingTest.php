<?php

declare(strict_types=1);

use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\TrackedGame;
use App\Models\User;
use Livewire\Livewire;

test('dashboard lists games in progress with their resume information', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->playing()->for($user)->create([
        'game_id' => Game::factory()->create(['title' => 'Hollow Quest']),
        'platform' => 'Switch',
        'progress' => 'Act II',
        'progress_percent' => 40,
    ]);
    JournalEntry::factory()->resumeGoal()->for($entry)->create(['body' => 'Find the hidden forge']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Currently playing', false)
        ->assertSee('Hollow Quest', false)
        ->assertSee('Switch', false)
        ->assertSee('Act II', false)
        ->assertSee('Find the hidden forge', false)
        ->assertSee(route('games.show', $entry->game).'#my-tracking', false);
});

test('only games with the playing status appear in currently playing', function (TrackedGameStatus $status): void {
    $user = User::factory()->create();
    TrackedGame::factory()->status($status)->for($user)->create();

    $component = Livewire::actingAs($user)->test('dashboard-game-list')
        ->assertSee('Nothing in progress right now');

    expect($component->instance()->currentlyPlaying)->toBeEmpty();
})->with([
    'paused' => TrackedGameStatus::Paused,
    'completed' => TrackedGameStatus::Completed,
    'dropped' => TrackedGameStatus::Dropped,
    'to play' => TrackedGameStatus::ToPlay,
]);

test('currently playing is sorted by most recent activity', function (): void {
    $user = User::factory()->create();
    $older = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => now()->subDays(5)]);
    $recent = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => now()->subHour()]);
    $never = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => null]);

    $component = Livewire::actingAs($user)->test('dashboard-game-list');

    expect($component->instance()->currentlyPlaying->pluck('id')->all())->toBe([$recent->id, $older->id, $never->id]);
});

test('currently playing never shows another user games', function (): void {
    $user = User::factory()->create();
    TrackedGame::factory()->playing()->create(['game_id' => Game::factory()->create(['title' => 'Somebody Else Game'])]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Somebody Else Game', false);
});

test('a game set to playing from the list appears and leaves when paused', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->toPlay()->for($user)->create();

    $component = Livewire::actingAs($user)->test('dashboard-game-list')
        ->call('updateStatus', $entry->game_id, TrackedGameStatus::Playing->value);

    expect($component->instance()->currentlyPlaying->pluck('id')->all())->toBe([$entry->id]);

    $component->call('updateStatus', $entry->game_id, TrackedGameStatus::Paused->value);

    expect($component->instance()->currentlyPlaying)->toBeEmpty()
        ->and($entry->statusChanges()->count())->toBe(2);
});

test('clearing the status from the list is recorded in history', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->playing()->for($user)->create();

    Livewire::actingAs($user)->test('dashboard-game-list')->call('updateStatus', $entry->game_id, '');

    expect($entry->fresh()->status)->toBeNull()
        ->and($entry->statusChanges()->first()->to_status)->toBeNull();
});

test('updating the status of an untracked game fails', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    Livewire::actingAs($user)->test('dashboard-game-list')->call('updateStatus', $game->id, TrackedGameStatus::Playing->value);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('dashboard keeps upcoming releases when nothing is being played', function (): void {
    $user = User::factory()->create();
    TrackedGame::factory()->for($user)->create([
        'game_id' => Game::factory()->create(['title' => 'Future Game', 'release_date' => now()->addMonth()]),
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Nothing in progress right now', false)
        ->assertSee('Upcoming releases', false)
        ->assertSee('Future Game', false);
});
