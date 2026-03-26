<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\News;
use App\Models\User;
use App\Services\DashboardFeedService;

test('items after last_feed_read_at are marked as new', function (): void {
    $user = User::factory()->create(['last_feed_read_at' => now()->subHour()]);
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    News::factory()->create([
        'game_id' => $game->id,
        'published_at' => now(),
    ]);

    $service = app(DashboardFeedService::class);
    $items = $service->getFeedItems($user, 'all', 10, 0, $user->last_feed_read_at);

    expect($items)->not->toBeEmpty()
        ->and($items[0]['is_new'])->toBeTrue();
});

test('items before last_feed_read_at are not marked as new', function (): void {
    $user = User::factory()->create(['last_feed_read_at' => now()->addHour()]);
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    News::factory()->create([
        'game_id' => $game->id,
        'published_at' => now(),
    ]);

    $service = app(DashboardFeedService::class);
    $items = $service->getFeedItems($user, 'all', 10, 0, $user->last_feed_read_at);

    expect($items)->not->toBeEmpty()
        ->and($items[0]['is_new'])->toBeFalse();
});

test('all items are not new when no last_feed_read_at', function (): void {
    $user = User::factory()->create(['last_feed_read_at' => null]);
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    News::factory()->create(['game_id' => $game->id, 'published_at' => now()]);

    $service = app(DashboardFeedService::class);
    $items = $service->getFeedItems($user, 'all', 10, 0, null);

    expect($items)->not->toBeEmpty()
        ->and($items[0]['is_new'])->toBeFalse();
});

test('visiting dashboard updates last_feed_read_at', function (): void {
    $user = User::factory()->create(['last_feed_read_at' => null]);
    $this->actingAs($user);

    $this->get(route('dashboard'));

    expect($user->fresh()->last_feed_read_at)->not->toBeNull();
});
