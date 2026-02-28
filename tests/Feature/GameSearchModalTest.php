<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Livewire\Livewire;

test('search spotlight and trigger are present when layout is loaded', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games', false);
    $response->assertSee('open-game-search', false);
    $response->assertSee('Type to search games', false);
});

test('search trigger documents keyboard shortcut in UI', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games (⌘K)', false);
});

test('authenticated user can track a game from search modal', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Unique Title For Search']);

    Livewire::actingAs($user)
        ->test('game-search-modal')
        ->set('query', 'Unique Title')
        ->call('trackGame', $game->id);

    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeTrue();
});

test('authenticated user can untrack a game from search modal', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Unique Title To Untrack']);
    $user->trackedGames()->attach($game);

    Livewire::actingAs($user)
        ->test('game-search-modal')
        ->set('query', 'Unique Title')
        ->call('untrackGame', $game->id);

    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeFalse();
});
