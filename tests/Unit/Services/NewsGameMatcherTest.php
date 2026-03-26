<?php

declare(strict_types=1);

use App\Models\Game;
use App\Services\NewsGameMatcher;

uses()->group('unit');

beforeEach(function (): void {
    $this->matcher = app(NewsGameMatcher::class);
});

test('findMatchingGame returns null when news title is empty', function (): void {
    $game = $this->matcher->findMatchingGame('   ');

    expect($game)->toBeNull();
});

test('findMatchingGame returns null when news title contains only punctuation', function (): void {
    $game = $this->matcher->findMatchingGame('... ---');

    expect($game)->toBeNull();
});

test('findMatchingGame skips games with empty title', function (): void {
    Game::factory()->create(['title' => '']);
    Game::factory()->create(['title' => 'Elden Ring']);

    $game = $this->matcher->findMatchingGame('Elden Ring news');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Elden Ring');
});

test('findMatchingGame returns null when only empty-titled games exist', function (): void {
    Game::factory()->create(['title' => '']);

    $game = $this->matcher->findMatchingGame('some news about something');

    expect($game)->toBeNull();
});

test('findMatchingGame skips games whose title reduces to no words', function (): void {
    Game::factory()->create(['title' => '...']);
    Game::factory()->create(['title' => 'Hollow Knight']);

    $game = $this->matcher->findMatchingGame('Hollow Knight new patch');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Hollow Knight');
});

test('findMatchingGame returns null when only punctuation-titled games exist', function (): void {
    Game::factory()->create(['title' => '...']);

    $game = $this->matcher->findMatchingGame('some news about something');

    expect($game)->toBeNull();
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

test('findMatchingGame does not match when game title is substring of a word in news', function (): void {
    Game::factory()->create(['title' => 'Strand']);

    $game = $this->matcher->findMatchingGame('Death Stranding 2 release date announced');

    expect($game)->toBeNull();
});

test('findMatchingGame matches when all game words appear as whole words in news', function (): void {
    Game::factory()->create(['title' => 'Death Stranding 2']);

    $game = $this->matcher->findMatchingGame('Death Stranding 2 release date announced');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Death Stranding 2');
});

test('findMatchingGame is case-insensitive', function (): void {
    Game::factory()->create(['title' => 'elden ring']);

    $game = $this->matcher->findMatchingGame('Elden Ring DLC Shadow of the Erdtree');

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('elden ring');
});
