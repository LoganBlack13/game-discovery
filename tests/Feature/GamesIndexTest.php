<?php

declare(strict_types=1);

use App\Enums\ReleaseStatus;
use App\Models\Game;
use App\Models\User;

test('games index page is accessible to guests', function (): void {
    $response = $this->get(route('games.index'));

    $response->assertSuccessful();
    $response->assertSee('Games', false);
});

test('games index page is accessible to authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('games.index'));

    $response->assertSuccessful();
});

test('games index shows all games by default', function (): void {
    Game::factory()->create(['title' => 'Alpha Game']);
    Game::factory()->create(['title' => 'Beta Game']);

    $response = $this->get(route('games.index'));

    $response->assertSuccessful();
    $response->assertSee('Alpha Game', false);
    $response->assertSee('Beta Game', false);
});

test('games index filters by search query', function (): void {
    Game::factory()->create(['title' => 'Hollow Knight']);
    Game::factory()->create(['title' => 'Dark Souls']);

    $response = $this->get(route('games.index', ['q' => 'Hollow']));

    $response->assertSuccessful();
    $response->assertSee('Hollow Knight', false);
    $response->assertDontSee('Dark Souls', false);
});

test('games index filters upcoming games', function (): void {
    Game::factory()->create([
        'title' => 'Future Game',
        'release_date' => now()->addMonths(3),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);
    Game::factory()->create([
        'title' => 'Past Game',
        'release_date' => now()->subMonths(3),
        'release_status' => ReleaseStatus::Released,
    ]);

    $response = $this->get(route('games.index', ['status' => 'upcoming']));

    $response->assertSuccessful();
    $response->assertSee('Future Game', false);
    $response->assertDontSee('Past Game', false);
});

test('games index filters released games', function (): void {
    Game::factory()->create([
        'title' => 'Released Game',
        'release_date' => now()->subMonths(3),
        'release_status' => ReleaseStatus::Released,
    ]);
    Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => now()->addMonths(3),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $response = $this->get(route('games.index', ['status' => 'released']));

    $response->assertSuccessful();
    $response->assertSee('Released Game', false);
    $response->assertDontSee('Upcoming Game', false);
});

test('games index links to individual game pages', function (): void {
    $game = Game::factory()->create(['title' => 'Linked Game']);

    $response = $this->get(route('games.index'));

    $response->assertSuccessful();
    $response->assertSee(route('games.show', $game), false);
});

test('games index filters by genre', function (): void {
    Game::factory()->create(['title' => 'RPG Adventure', 'genres' => ['RPG', 'Adventure']]);
    Game::factory()->create(['title' => 'Pure Shooter', 'genres' => ['Shooter']]);

    $response = $this->get(route('games.index', ['genre' => 'RPG']));

    $response->assertSuccessful();
    $response->assertSee('RPG Adventure', false);
    $response->assertDontSee('Pure Shooter', false);
});

test('games index shows all games when genre is empty', function (): void {
    Game::factory()->create(['title' => 'RPG Game', 'genres' => ['RPG']]);
    Game::factory()->create(['title' => 'Shooter Game', 'genres' => ['Shooter']]);

    $response = $this->get(route('games.index', ['genre' => '']));

    $response->assertSuccessful();
    $response->assertSee('RPG Game', false);
    $response->assertSee('Shooter Game', false);
});
