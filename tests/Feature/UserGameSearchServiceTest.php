<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use App\Services\UserGameSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search returns at most 10 results when more match', function (): void {
    foreach (range(1, 15) as $i) {
        Game::factory()->create(['title' => "Matching Game {$i}"]);
    }
    $user = User::factory()->create();
    $service = app(UserGameSearchService::class);

    $results = $service->search($user, 'Matching Game', 10);

    expect($results)->toHaveCount(10);
    $results->each(function ($result): void {
        expect($result->game->title)->toContain('Matching Game');
        expect($result->game->title)->not->toBeEmpty();
    });
});

test('search result includes tracking state when user tracks some games', function (): void {
    $user = User::factory()->create();
    $tracked = Game::factory()->create(['title' => 'Tracked Game One']);
    $notTracked = Game::factory()->create(['title' => 'Not Tracked Game Two']);
    $user->trackedGames()->attach($tracked);
    $service = app(UserGameSearchService::class);

    $results = $service->search($user, 'Game');

    expect($results)->toHaveCount(2);
    $trackedResult = $results->first(fn ($r) => $r->game->id === $tracked->id);
    $notTrackedResult = $results->first(fn ($r) => $r->game->id === $notTracked->id);
    expect($trackedResult->isTracked)->toBeTrue();
    expect($notTrackedResult->isTracked)->toBeFalse();
});

test('search returns empty for guest when query is empty', function (): void {
    $service = app(UserGameSearchService::class);

    $results = $service->search(null, '   ', 10);

    expect($results)->toHaveCount(0);
});

test('search returns results with cover image or title', function (): void {
    Game::factory()->create(['title' => 'Game With Cover', 'cover_image' => 'https://example.com/cover.jpg']);
    Game::factory()->create(['title' => 'Game No Cover', 'cover_image' => null]);
    $service = app(UserGameSearchService::class);

    $results = $service->search(null, 'Game');

    expect($results)->toHaveCount(2);
    foreach ($results as $result) {
        expect($result->game->title)->not->toBeEmpty();
    }
});
