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

test('search modal shows at most 10 results', function (): void {
    foreach (range(1, 15) as $i) {
        Game::factory()->create(['title' => "Cap Test Game {$i}"]);
    }

    $component = Livewire::test('game-search-modal')
        ->set('query', 'Cap Test Game');

    $component->assertSee('Cap Test Game 1');
    $rendered = $component->html();
    $count = mb_substr_count($rendered, 'data-game-url=');
    expect($count)->toBeLessThanOrEqual(10);
});

test('guest sees log in to track in search results', function (): void {
    Game::factory()->create(['title' => 'Guest Search Game']);

    Livewire::test('game-search-modal')
        ->set('query', 'Guest Search')
        ->assertSee('Log in to track');
});
