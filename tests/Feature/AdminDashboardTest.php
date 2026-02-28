<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

uses()->group('admin');

test('admin dashboard shows stats and inline add-game section', function (): void {
    Game::factory()->count(3)->create();
    Game::factory()->create(['created_at' => now()->subDays(3)]);

    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin', false);
    $response->assertSee('Total games', false);
    $response->assertSee('4', false);
    $response->assertSee('Added this week', false);
    $response->assertSee('Add game from RAWG', false);
    $response->assertSee('Latest games', false);
    $response->assertSee('See more', false);
    $response->assertSee(route('admin.games.index'), false);
});

test('admin dashboard shows latest games table', function (): void {
    $game = Game::factory()->create(['title' => 'Unique Game Title']);

    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Unique Game Title', false);
});
