<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('opens search palette via header button and shows search UI', function (): void {
    $page = visit('/');

    $page->assertNotPresent('[x-cloak]')
        ->click('button[aria-label="Search games (⌘K)"]')
        ->assertVisible('.spotlight-input')
        ->assertSee('Search games')
        ->assertSee('Type to search games');
});

it('authenticated user can track a game from search results', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Browser Track Me']);

    $this->actingAs($user);
    $page = visit('/');
    $page->assertNotPresent('[x-cloak]')
        ->click('button[aria-label="Search games (⌘K)"]')
        ->assertVisible('.spotlight-input')
        ->type('.spotlight-input', 'Browser Track Me')
        ->press('Track game');

    $page->assertSee('Remove from tracking');
    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeTrue();
});
