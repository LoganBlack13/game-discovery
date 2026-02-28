<?php

declare(strict_types=1);

use App\Enums\ReleaseStatus;
use App\Models\Game;

test('slug is derived from title when creating game', function (): void {
    $game = Game::factory()->create(['title' => 'Foo']);

    expect($game->slug)->toBe('foo');
});

test('upcoming scope returns games with future release date or announced status', function (): void {
    $upcoming = Game::factory()->create([
        'release_date' => now()->addDays(10),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);
    Game::factory()->create([
        'release_date' => now()->subDays(5),
        'release_status' => ReleaseStatus::Released,
    ]);

    $ids = Game::query()->upcoming()->pluck('id')->all();

    expect($ids)->toContain($upcoming->id)->and($ids)->toHaveCount(1);
});

test('released scope returns games with past release date or released status', function (): void {
    $released = Game::factory()->create([
        'release_date' => now()->subDays(5),
        'release_status' => ReleaseStatus::Released,
    ]);
    Game::factory()->create([
        'release_date' => now()->addDays(10),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $ids = Game::query()->released()->pluck('id')->all();

    expect($ids)->toContain($released->id)->and($ids)->toHaveCount(1);
});
