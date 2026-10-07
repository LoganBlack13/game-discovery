<?php

declare(strict_types=1);

use App\Enums\BacklogPriority;
use App\Enums\ReleaseStatus;
use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\TrackedGame;
use App\Models\User;
use Livewire\Livewire;

test('dashboard shows the backlog section', function (): void {
    $user = User::factory()->create();
    TrackedGame::factory()->toPlay()->for($user)->create(['game_id' => Game::factory()->create(['title' => 'Backlog Gem'])]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My backlog', false)
        ->assertSee('Backlog Gem', false);
});

test('backlog shows an empty state', function (): void {
    Livewire::actingAs(User::factory()->create())->test('dashboard-backlog')
        ->assertSee('Your backlog is empty.');
});

test('pinned games appear in play next and others in the backlog', function (): void {
    $user = User::factory()->create();
    $pinned = TrackedGame::factory()->toPlay()->for($user)->create(['is_up_next' => true]);
    $other = TrackedGame::factory()->toPlay()->for($user)->create();
    TrackedGame::factory()->playing()->for($user)->create();

    $component = Livewire::actingAs($user)->test('dashboard-backlog');

    expect($component->instance()->playNext->pluck('id')->all())->toBe([$pinned->id])
        ->and($component->instance()->backlog->pluck('id')->all())->toBe([$other->id])
        ->and($component->instance()->backlogCount)->toBe(1);
});

test('play next can be toggled', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->toPlay()->for($user)->create();

    $component = Livewire::actingAs($user)->test('dashboard-backlog')->call('togglePlayNext', $entry->id);
    expect($entry->fresh()->is_up_next)->toBeTrue();

    $component->call('togglePlayNext', $entry->id);
    expect($entry->fresh()->is_up_next)->toBeFalse();
});

test('priority can be set and cleared without touching game metadata', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->toPlay()->for($user)->create();
    $gameBefore = $entry->game->fresh()->toArray();

    $component = Livewire::actingAs($user)->test('dashboard-backlog')->call('setPriority', $entry->id, BacklogPriority::High->value);
    expect($entry->fresh()->priority)->toBe(BacklogPriority::High);

    $component->call('setPriority', $entry->id, '');
    expect($entry->fresh()->priority)->toBeNull()
        ->and($entry->game->fresh()->toArray())->toBe($gameBefore);
});

test('games can be reordered manually', function (): void {
    $user = User::factory()->create();
    $first = TrackedGame::factory()->toPlay()->for($user)->create();
    $this->travel(1)->minute();
    $second = TrackedGame::factory()->toPlay()->for($user)->create();

    $component = Livewire::actingAs($user)->test('dashboard-backlog')->call('move', $second->id, -1);

    expect($component->instance()->backlog->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('starting a game removes it from play next but keeps its history', function (): void {
    $user = User::factory()->create();
    $entry = TrackedGame::factory()->toPlay()->for($user)->create(['is_up_next' => true]);

    $component = Livewire::actingAs($user)->test('dashboard-backlog')
        ->call('start', $entry->id)
        ->assertDispatched('tracking-updated');

    $entry->refresh();
    expect($entry->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->is_up_next)->toBeFalse()
        ->and($entry->statusChanges()->count())->toBe(1)
        ->and($component->instance()->playNext)->toBeEmpty();
});

test('backlog can be filtered by platform and availability', function (): void {
    $user = User::factory()->create();
    $pcReleased = TrackedGame::factory()->toPlay()->for($user)->create(['game_id' => Game::factory()->create([
        'platforms' => ['PC'], 'release_date' => now()->subYear(), 'release_status' => ReleaseStatus::Released,
    ])]);
    $switchUpcoming = TrackedGame::factory()->toPlay()->for($user)->create(['game_id' => Game::factory()->create([
        'platforms' => ['Switch'], 'release_date' => now()->addYear(), 'release_status' => ReleaseStatus::ComingSoon,
    ])]);

    $component = Livewire::actingAs($user)->test('dashboard-backlog')->set('platform', 'PC');
    expect($component->instance()->backlog->pluck('id')->all())->toBe([$pcReleased->id]);

    $component->set('platform', '')->set('availability', 'upcoming');
    expect($component->instance()->backlog->pluck('id')->all())->toBe([$switchUpcoming->id]);

    $component->set('availability', 'released');
    expect($component->instance()->backlog->pluck('id')->all())->toBe([$pcReleased->id]);

    $component->set('platform', 'Xbox')->assertSee('No “To Play” game matches these filters.');
});

test('large backlogs are paginated with show more', function (): void {
    $user = User::factory()->create();
    TrackedGame::factory()->toPlay()->for($user)->count(45)->create();

    $component = Livewire::actingAs($user)->test('dashboard-backlog');
    expect($component->instance()->backlog)->toHaveCount(20)
        ->and($component->instance()->backlogCount)->toBe(45);

    $component->call('showMore')->call('showMore');
    expect($component->instance()->backlog)->toHaveCount(45);

    $component->set('platform', 'PC');
    expect($component->get('limit'))->toBe(20);
});

test('another user backlog entry cannot be changed', function (): void {
    $foreign = TrackedGame::factory()->toPlay()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('dashboard-backlog')
        ->call('togglePlayNext', $foreign->id)
        ->assertNotFound();

    expect($foreign->fresh()->is_up_next)->toBe($foreign->is_up_next);
});
