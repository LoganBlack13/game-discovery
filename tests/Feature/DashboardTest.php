<?php

declare(strict_types=1);

use App\Enums\GameActivityType;
use App\Models\Game;
use App\Models\GameActivity;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\Date;
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
    $response->assertSee('xl:grid-cols-5', false);
});

test('authenticated user sees hero and Up next when they have tracked games with future release dates', function (): void {
    $user = User::factory()->create();
    $upcoming = Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => Date::now()->addMonths(2),
    ]);
    $user->trackedGames()->attach($upcoming);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Up next', false);
    $response->assertSee('Upcoming Game', false);
    $response->assertSee('Until release', false);
});

test('authenticated user sees three next games when they have two to four upcoming', function (): void {
    $user = User::factory()->create();
    foreach (['First Game', 'Second Game', 'Third Game', 'Fourth Game'] as $title) {
        $game = Game::factory()->create([
            'title' => $title,
            'release_date' => Date::now()->addMonths(1),
        ]);
        $user->trackedGames()->attach($game);
    }

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('First Game', false);
    $response->assertSee('Second Game', false);
    $response->assertSee('Third Game', false);
    $response->assertSee('Fourth Game', false);
});

test('dashboard renders game preview panel in DOM', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Panel Game']);
    $user->trackedGames()->attach($game);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('game-preview-title', false);
    $response->assertSee('View game', false);
    $response->assertSee('Estimated completion:', false);
});

test('dashboard shows Upcoming releases section with countdown and news count when user has upcoming tracked games', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create([
        'title' => 'Upcoming Release',
        'release_date' => Date::now()->addDays(30),
    ]);
    $user->trackedGames()->attach($game);
    News::factory()->create(['game_id' => $game->id, 'title' => 'News one', 'published_at' => now()]);
    News::factory()->create(['game_id' => $game->id, 'title' => 'News two', 'published_at' => now()->subDay()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Upcoming releases', false);
    $response->assertSee("Track the games you're waiting for", false);
    $response->assertSee('Upcoming Release', false);
    $response->assertSee('2 news', false);
    $response->assertSee('Browse more games', false);
});

test('authenticated user sees Recent updates feed with news for tracked games only', function (): void {
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

test('news sidebar loads initial 10 items and loadMore adds more', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'News Game']);
    $user->trackedGames()->attach($game);
    foreach (range(1, 15) as $i) {
        News::factory()->create([
            'game_id' => $game->id,
            'title' => 'News item '.$i,
            'published_at' => now()->subDays($i),
        ]);
    }

    $component = Livewire::actingAs($user)->test('dashboard-news-sidebar');

    $component->assertSee('News item 1');
    $component->assertSee('News item 10');
    $component->assertDontSee('News item 11');

    $component->call('loadMore');

    $component->assertSee('News item 11');
    $component->assertSee('News item 15');
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
    GameActivity::query()->create([
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
    GameActivity::query()->create([
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

test('user can update the status of a tracked game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    Livewire::actingAs($user)
        ->test('dashboard-game-list')
        ->call('updateStatus', $game->id, 'playing');

    expect($user->trackedGames()->where('game_id', $game->id)->first()?->pivot?->status)
        ->toBe('playing');
});

test('user can clear the status of a tracked game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game, ['status' => 'playing']);

    Livewire::actingAs($user)
        ->test('dashboard-game-list')
        ->call('updateStatus', $game->id, '');

    expect($user->trackedGames()->where('game_id', $game->id)->first()?->pivot?->status)
        ->toBeNull();
});

test('status filter only shows games with matching status', function (): void {
    $user = User::factory()->create();
    $playing = Game::factory()->create(['title' => 'Playing Game']);
    $dropped = Game::factory()->create(['title' => 'Dropped Game']);
    $user->trackedGames()->attach($playing, ['status' => 'playing']);
    $user->trackedGames()->attach($dropped, ['status' => 'dropped']);

    $component = Livewire::actingAs($user)
        ->test('dashboard-game-list')
        ->set('statusFilter', 'playing');

    $component->assertSee('Playing Game');
    $component->assertDontSee('Dropped Game');
});
