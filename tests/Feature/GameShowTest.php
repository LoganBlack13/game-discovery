<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameActivity;
use App\Models\User;

test('game show page displays game by slug', function (): void {
    $game = Game::factory()->create(['slug' => 'my-game', 'title' => 'My Game']);

    $response = $this->get(route('games.show', $game));

    $response->assertOk();
    $response->assertSee('my-game', false);
    $response->assertSee('My Game', false);
});

test('game show returns 404 for invalid slug', function (): void {
    $response = $this->get(route('games.show', ['game' => 'non-existent-slug']));

    $response->assertNotFound();
});

test('game show displays activity timeline when activities exist', function (): void {
    $game = Game::factory()->create(['title' => 'Active Game']);
    GameActivity::factory()->releaseDateChanged()->create([
        'game_id' => $game->id,
        'description' => 'From Jan 1, 2025 to Mar 1, 2025',
        'occurred_at' => now()->subDays(5),
    ]);
    GameActivity::factory()->gameReleased()->create([
        'game_id' => $game->id,
        'occurred_at' => now()->subDay(),
    ]);

    $response = $this->get(route('games.show', $game));

    $response->assertOk();
    $response->assertSee('Activity', false);
    $response->assertSee('Release date changed', false);
    $response->assertSee('From Jan 1, 2025 to Mar 1, 2025', false);
    $response->assertSee('Game released', false);
});

test('game show does not show activity section when no activities exist', function (): void {
    $game = Game::factory()->create();

    $response = $this->get(route('games.show', $game));

    $response->assertOk();
    $response->assertDontSee('Activity', false);
});

test('game show displays track button for authenticated user', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Trackable Game']);

    $response = $this->actingAs($user)->get(route('games.show', $game));

    $response->assertOk();
    $response->assertSee('Track game', false);
});

test('game show displays remove from tracking button when game is tracked', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Tracked Game']);
    $user->trackedGames()->attach($game);

    $response = $this->actingAs($user)->get(route('games.show', $game));

    $response->assertOk();
    $response->assertSee('Remove from tracking', false);
});
