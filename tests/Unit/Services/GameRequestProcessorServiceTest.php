<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use App\Models\GameRequest;
use App\Services\GameRequestProcessorService;
use Illuminate\Support\Facades\Bus;

uses()->group('unit');

test('dispatches SyncGameJob and updates request when provider returns result and game does not exist', function (): void {
    Bus::fake();

    $request = GameRequest::factory()->create([
        'normalized_title' => 'elden ring',
        'display_title' => 'Elden Ring',
        'request_count' => 1,
        'status' => 'pending',
        'game_id' => null,
    ]);

    $searchResult = [
        'title' => 'Elden Ring',
        'slug' => 'elden-ring',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => '2022-02-25',
        'release_status' => 'released',
        'external_id' => '12345',
        'external_source' => 'rawg',
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($searchResult): void {
        $mock->shouldReceive('search')->with('Elden Ring')->andReturn([$searchResult]);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    Bus::assertDispatched(SyncGameJob::class, fn (SyncGameJob $job): bool => $job->externalId === '12345' && $job->externalSource === 'rawg');
});

test('does not load requests that already have game_id (pending scope excludes them)', function (): void {
    Bus::fake();

    $game = Game::factory()->create();
    GameRequest::factory()->create([
        'status' => 'pending',
        'game_id' => $game->id,
    ]);
    $pendingRequest = GameRequest::factory()->create([
        'normalized_title' => 'only pending one',
        'display_title' => 'Only Pending One',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->with('Only Pending One')->andReturn([]);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    Bus::assertNotDispatched(SyncGameJob::class);
    $pendingRequest->refresh();
    expect($pendingRequest->game_id)->toBeNull();
});

test('does not dispatch when search returns no results and request stays pending', function (): void {
    Bus::fake();

    $request = GameRequest::factory()->create([
        'normalized_title' => 'unknown game xyz',
        'display_title' => 'Unknown Game XYZ',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->with('Unknown Game XYZ')->andReturn([]);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    Bus::assertNotDispatched(SyncGameJob::class);
    $request->refresh();
    expect($request->game_id)->toBeNull()
        ->and($request->status)->toBe('pending');
});

test('links request to existing game by external_id and does not dispatch', function (): void {
    Bus::fake();

    $existingGame = Game::factory()->create([
        'external_id' => '999',
        'external_source' => 'rawg',
    ]);

    $request = GameRequest::factory()->create([
        'normalized_title' => 'existing game',
        'display_title' => 'Existing Game',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $searchResult = [
        'title' => 'Existing Game',
        'slug' => 'existing-game',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => null,
        'release_status' => 'released',
        'external_id' => '999',
        'external_source' => 'rawg',
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($searchResult): void {
        $mock->shouldReceive('search')->with('Existing Game')->andReturn([$searchResult]);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    Bus::assertNotDispatched(SyncGameJob::class);
    $request->refresh();
    expect($request->game_id)->toBe($existingGame->id)
        ->and($request->status)->toBe('added')
        ->and($request->added_at)->not->toBeNull();
});

test('skips request when matching game already in database by title and does not dispatch', function (): void {
    Bus::fake();

    $existingGame = Game::factory()->create(['title' => 'Elden Ring']);

    $request = GameRequest::factory()->create([
        'normalized_title' => 'elden ring',
        'display_title' => 'Elden Ring',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $provider = $this->mock(GameDataProvider::class, function ($mock): void {
        $mock->shouldReceive('search')->never();
    });

    $this->mock(GameDataProviderResolver::class, function ($mock): void {
        $mock->shouldReceive('resolve')->never();
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    Bus::assertNotDispatched(SyncGameJob::class);
    $request->refresh();
    expect($request->game_id)->toBe($existingGame->id)
        ->and($request->status)->toBe('added')
        ->and($request->added_at)->not->toBeNull();
});

test('marks request added when SyncGameJob creates the game synchronously', function (): void {
    $request = GameRequest::factory()->create([
        'normalized_title' => 'brand new game',
        'display_title' => 'Brand New Game',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $gameDetails = [
        'title' => 'Brand New Game',
        'slug' => 'brand-new-game',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => null,
        'release_status' => 'released',
        'external_id' => 'ext-brand-new',
        'external_source' => 'rawg',
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($gameDetails): void {
        $mock->shouldReceive('search')->with('Brand New Game')->andReturn([$gameDetails]);
        $mock->shouldReceive('getGameDetails')->with('ext-brand-new')->andReturn($gameDetails);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
    });

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5);

    $request->refresh();
    expect($request->status)->toBe('added')
        ->and($request->game_id)->not->toBeNull();
});

test('writes progress to cache when runId provided', function (): void {
    $runId = 'test-run-123';
    $request = GameRequest::factory()->create([
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

    $service = resolve(GameRequestProcessorService::class);
    $service->process(5, $runId);

    $key = 'game_requests:progress:'.$runId;
    $progress = cache($key);
    expect($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('completed')
        ->and($progress['processed'])->toBe(1);
});

test('writes failed progress to cache when exception is thrown', function (): void {
    $runId = 'fail-run-123';
    GameRequest::factory()->create([
        'normalized_title' => 'will throw',
        'display_title' => 'Will Throw',
        'status' => 'pending',
        'game_id' => null,
    ]);

    $this->mock(GameDataProviderResolver::class, function ($mock): void {
        $mock->shouldReceive('resolve')->with('rawg')->andThrow(new RuntimeException('Provider error'));
    });

    $service = resolve(GameRequestProcessorService::class);

    try {
        $service->process(5, $runId);
    } catch (Throwable) {
        // expected
    }

    $progress = cache('game_requests:progress:'.$runId);
    expect($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('failed')
        ->and($progress['error'])->toBe('Provider error');
});
