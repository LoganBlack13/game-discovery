<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

test('user tracked games contains game when attached', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    $user->trackedGames()->attach($game);

    $user->refresh();

    expect($user->trackedGames->contains('id', $game->id))->toBeTrue();
});
