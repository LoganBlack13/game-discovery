<?php

declare(strict_types=1);

use App\Models\Game;

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
