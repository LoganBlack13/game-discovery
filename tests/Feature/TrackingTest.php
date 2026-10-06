<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

test('guest cannot track a game', function (): void {
    $game = Game::factory()->create();

    $response = $this->post(route('games.track', $game));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated user can track a game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    $response = $this->actingAs($user)->post(route('games.track', $game));

    $response->assertRedirect();

    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeTrue();
});

test('authenticated user can untrack a game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    $response = $this->actingAs($user)->delete(route('games.untrack', $game));

    $response->assertRedirect();

    $user->refresh();
    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeFalse();
});

test('authenticated user can track a game via JSON request', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    $response = $this->actingAs($user)->postJson(route('games.track', $game));

    $response->assertSuccessful();
    $response->assertJson(['tracked' => true]);

    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeTrue();
});

test('authenticated user can untrack a game via JSON request', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->trackedGames()->attach($game);

    $response = $this->actingAs($user)->deleteJson(route('games.untrack', $game));

    $response->assertSuccessful();
    $response->assertJson(['tracked' => false]);

    expect($user->trackedGames()->where('game_id', $game->id)->exists())->toBeFalse();
});

test('tracking a game starts its personal journey as watching', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    $this->actingAs($user)->post(route('games.track', $game))->assertRedirect();
    $this->actingAs($user)->post(route('games.track', $game))->assertRedirect();

    $entry = $user->trackedGameEntries()->where('game_id', $game->id)->sole();
    expect($entry->status)->toBe(App\Enums\TrackedGameStatus::Watching)
        ->and($entry->statusChanges()->count())->toBe(1);
});
