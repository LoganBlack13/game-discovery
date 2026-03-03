<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('opens search palette via header button and shows search UI', function (): void {
    $page = visit('/');

    $page->click('button[aria-label="Search games (⌘K)"]')
        ->assertSee('Search games')
        ->assertSee('Type to search games');
});

it('shows at most 10 results when searching', function (): void {
    foreach (range(1, 15) as $i) {
        Game::factory()->create(['title' => "Browser Cap Game {$i}"]);
    }

    $page = visit('/');
    $page->click('button[aria-label="Search games (⌘K)"]')
        ->type('Search games…', 'Browser Cap Game');

    $page->assertSee('Browser Cap Game 1');
    $html = $page->content();
    $count = mb_substr_count($html, 'data-game-url=');
    expect($count)->toBeLessThanOrEqual(10);
});

it('authenticated user can track a game from search results', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Browser Track Me']);

    $page = visit('/');
    $this->actingAs($user);
    $page->click('button[aria-label="Search games (⌘K)"]')
        ->type('Search games…', 'Browser Track Me')
        ->press('Track game');

    $page->assertSee('Remove from tracking');
    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeTrue();
});
