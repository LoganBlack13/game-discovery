<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver;
use App\Jobs\ProcessGameRequestsJob;
use App\Models\GameRequest;

test('game-requests:process command runs and outputs run id', function (): void {
    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->andReturn([]);
    });
    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $this->artisan('game-requests:process')
        ->assertSuccessful()
        ->expectsOutputToContain('Run ID:');
});

test('game-requests:process dispatches job and job writes progress to cache when run with sync queue', function (): void {
    $runId = 'test-run-123';
    GameRequest::factory()->create([
        'normalized_title' => 'foo',
        'display_title' => 'Foo',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->andReturn([]);
    });
    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    ProcessGameRequestsJob::dispatch($runId, 5);

    $progress = cache("game_requests:progress:{$runId}");
    expect($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('completed')
        ->and($progress)->toHaveKey('processed');
});

test('game-requests:process command accepts limit option', function (): void {
    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->andReturn([]);
    });
    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $this->artisan('game-requests:process', ['--limit' => 3])
        ->assertSuccessful();
});
