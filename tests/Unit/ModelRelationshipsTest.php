<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameRequest;
use App\Models\GameRequestVote;
use App\Models\TrackedGame;
use App\Models\User;

uses()->group('unit');

test('TrackedGame belongs to user', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $tracked = TrackedGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    expect($tracked->user)->toBeInstanceOf(User::class)
        ->and($tracked->user->id)->toBe($user->id);
});

test('TrackedGame belongs to game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $tracked = TrackedGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    expect($tracked->game)->toBeInstanceOf(Game::class)
        ->and($tracked->game->id)->toBe($game->id);
});

test('Game scopeByReleaseDate orders by release date', function (): void {
    $game2 = Game::factory()->create(['title' => 'Second', 'release_date' => '2025-06-01']);
    $game1 = Game::factory()->create(['title' => 'First', 'release_date' => '2025-01-01']);

    $result = Game::query()->byReleaseDate()->get();

    expect($result->first()->id)->toBe($game1->id);
});

test('Game getRouteKeyName returns slug', function (): void {
    $game = new Game;

    expect($game->getRouteKeyName())->toBe('slug');
});

test('Game hasMany gameRequests', function (): void {
    $game = Game::factory()->create();
    GameRequest::factory()->create(['game_id' => $game->id, 'status' => 'added']);

    expect($game->gameRequests)->toHaveCount(1);
});

test('GameRequest belongs to game', function (): void {
    $game = Game::factory()->create();
    $request = GameRequest::factory()->create(['game_id' => $game->id, 'status' => 'added']);

    expect($request->game)->toBeInstanceOf(Game::class)
        ->and($request->game->id)->toBe($game->id);
});

test('GameRequestVote belongs to game request', function (): void {
    $user = User::factory()->create();
    $request = GameRequest::factory()->create();
    $vote = GameRequestVote::create([
        'game_request_id' => $request->id,
        'user_id' => $user->id,
    ]);

    expect($vote->gameRequest)->toBeInstanceOf(GameRequest::class)
        ->and($vote->gameRequest->id)->toBe($request->id);
});

test('GameRequestVote belongs to user', function (): void {
    $user = User::factory()->create();
    $request = GameRequest::factory()->create();
    $vote = GameRequestVote::create([
        'game_request_id' => $request->id,
        'user_id' => $user->id,
    ]);

    expect($vote->user)->toBeInstanceOf(User::class)
        ->and($vote->user->id)->toBe($user->id);
});

test('User hasMany gameRequestVotes', function (): void {
    $user = User::factory()->create();
    $request = GameRequest::factory()->create();
    GameRequestVote::create([
        'game_request_id' => $request->id,
        'user_id' => $user->id,
    ]);

    expect($user->gameRequestVotes)->toHaveCount(1);
});
