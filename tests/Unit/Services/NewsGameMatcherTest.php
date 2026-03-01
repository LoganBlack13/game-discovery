<?php

declare(strict_types=1);

use App\Models\Game;
use App\Services\NewsGameMatcher;

uses()->group('unit');

beforeEach(function (): void {
    $this->matcher = app(NewsGameMatcher::class);
});

test('findMatchingGame returns game when title appears in news title', function (): void {
    Game::factory()->create(['title' => 'Elden Ring']);

    $game = $this->matcher->findMatchingGame('Elden Ring DLC announced');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Elden Ring');
});

test('findMatchingGame returns null when no game title matches', function (): void {
    Game::factory()->create(['title' => 'Elden Ring']);

    $game = $this->matcher->findMatchingGame('Random unrelated article');

    expect($game)->toBeNull();
});

test('longest matching title wins when multiple games could match', function (): void {
    Game::factory()->create(['title' => 'Ring']);
    Game::factory()->create(['title' => 'Elden Ring']);

    $game = $this->matcher->findMatchingGame('Elden Ring expansion news');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Elden Ring');
});

test('findMatchingGame only considers games in database', function (): void {
    $created = Game::factory()->create(['title' => 'Hollow Knight']);

    $game = $this->matcher->findMatchingGame('Hollow Knight Silksong update');

    expect($game)->not->toBeNull()
        ->and($game->id)->toBe($created->id)
        ->and($game->title)->toBe('Hollow Knight');
});
