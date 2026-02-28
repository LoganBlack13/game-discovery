<?php

declare(strict_types=1);

use App\Enums\GameActivityType;
use App\Models\Game;
use App\Models\GameActivity;
use App\Models\News;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('guest is redirected to login when visiting dashboard', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated user sees only their tracked games on dashboard', function (): void {
    $user = User::factory()->create();
    $tracked = Game::factory()->create(['title' => 'Tracked Game']);
    $other = Game::factory()->create(['title' => 'Other Game']);
    $user->trackedGames()->attach($tracked);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Tracked Game', false);
    $response->assertDontSee('Other Game', false);
});

test('authenticated user sees Up next when they have tracked games with future release dates', function (): void {
    $user = User::factory()->create();
    $upcoming = Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => Carbon::now()->addMonths(2),
    ]);
    $user->trackedGames()->attach($upcoming);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Up next', false);
    $response->assertSee('Upcoming Game', false);
});

test('authenticated user sees Recent updates section and only their tracked games updates', function (): void {
    $user = User::factory()->create();
    $tracked = Game::factory()->create(['title' => 'My Tracked Game']);
    $user->trackedGames()->attach($tracked);
    News::factory()->create([
        'game_id' => $tracked->id,
        'title' => 'Exclusive news for my game',
        'published_at' => now(),
    ]);
    $other = Game::factory()->create(['title' => 'Someone Elses Game']);
    News::factory()->create([
        'game_id' => $other->id,
        'title' => 'News for untracked game',
        'published_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Recent updates', false);
    $response->assertSee('Exclusive news for my game', false);
    $response->assertDontSee('News for untracked game', false);
});

test('feed filter News only shows only news items', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);
    News::factory()->create([
        'game_id' => $game->id,
        'title' => 'Only news item',
        'published_at' => now(),
    ]);
    GameActivity::create([
        'game_id' => $game->id,
        'type' => GameActivityType::ReleaseDateChanged,
        'title' => 'Release date changed',
        'description' => 'From Jan to Feb',
        'occurred_at' => now(),
    ]);

    $component = Livewire::actingAs($user)->test('dashboard-feed')->set('filter', 'news');

    $component->assertSee('Only news item');
    $component->assertDontSee('Release date changed');
});

test('feed filter Release updates only shows only activity items', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);
    News::factory()->create([
        'game_id' => $game->id,
        'title' => 'News headline',
        'published_at' => now(),
    ]);
    GameActivity::create([
        'game_id' => $game->id,
        'type' => GameActivityType::GameReleased,
        'title' => 'Game released today',
        'description' => 'Released',
        'occurred_at' => now(),
    ]);

    $component = Livewire::actingAs($user)->test('dashboard-feed')->set('filter', 'release');

    $component->assertSee('Game released today');
    $component->assertDontSee('News headline');
});
