<?php

declare(strict_types=1);

use App\Models\Game;

test('welcome page loads and shows hero content', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Discover your next game');
    $response->assertSee('Explore games');
    $response->assertSee('Trending now');
});

test('welcome page shows real upcoming popular and recently released games', function (): void {
    $upcoming = Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => now()->addDays(30),
    ]);
    $released = Game::factory()->create([
        'title' => 'Released Game',
        'release_date' => now()->subDays(10),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming Game', false);
    $response->assertSee('Released Game', false);
    $response->assertSee('Coming soon', false);
    $response->assertSee('Recently released', false);
});
