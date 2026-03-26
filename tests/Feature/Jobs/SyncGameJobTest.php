<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver;
use App\Enums\GameActivityType;
use App\Enums\ReleaseStatus;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use App\Models\GameActivity;
use App\Services\GameActivityRecorder;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $this->externalId = '12345';
    $this->externalSource = 'rawg';
});

test('sync creates GameActivity when release date changes', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'title' => 'Existing Game',
        'release_date' => Date::now()->addMonths(2),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $newReleaseDate = Date::now()->addMonth()->format('Y-m-d');
    $details = [
        'title' => 'Existing Game',
        'slug' => $game->slug,
        'description' => $game->description,
        'cover_image' => $game->cover_image,
        'developer' => $game->developer,
        'publisher' => $game->publisher,
        'genres' => $game->genres,
        'platforms' => $game->platforms,
        'release_date' => $newReleaseDate,
        'release_status' => ReleaseStatus::ComingSoon->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource, $game->id)->handle($resolver, resolve(GameActivityRecorder::class));

    $activity = GameActivity::query()
        ->where('game_id', $game->id)
        ->where('type', GameActivityType::ReleaseDateChanged)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->title)->toContain('Release date')
        ->and($activity->occurred_at)->not->toBeNull();
});

test('sync creates GameActivity when release date is announced for first time', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'release_date' => null,
        'release_status' => ReleaseStatus::Announced,
    ]);

    $newReleaseDate = Date::now()->addMonths(3)->format('Y-m-d');
    $details = [
        'title' => $game->title,
        'slug' => $game->slug,
        'description' => $game->description,
        'cover_image' => $game->cover_image,
        'developer' => $game->developer,
        'publisher' => $game->publisher,
        'genres' => $game->genres,
        'platforms' => $game->platforms,
        'release_date' => $newReleaseDate,
        'release_status' => ReleaseStatus::ComingSoon->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource, $game->id)->handle($resolver, resolve(GameActivityRecorder::class));

    $activity = GameActivity::query()
        ->where('game_id', $game->id)
        ->where('type', GameActivityType::ReleaseDateAnnounced)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->title)->toContain('Release date');
});

test('sync creates GameActivity when game becomes released', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'release_date' => Date::now()->addWeek(),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $pastDate = Date::now()->subDay()->format('Y-m-d');
    $details = [
        'title' => $game->title,
        'slug' => $game->slug,
        'description' => $game->description,
        'cover_image' => $game->cover_image,
        'developer' => $game->developer,
        'publisher' => $game->publisher,
        'genres' => $game->genres,
        'platforms' => $game->platforms,
        'release_date' => $pastDate,
        'release_status' => ReleaseStatus::Released->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource, $game->id)->handle($resolver, resolve(GameActivityRecorder::class));

    $activity = GameActivity::query()
        ->where('game_id', $game->id)
        ->where('type', GameActivityType::GameReleased)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->title)->toContain('released');
});

test('sync does not update when existing game has identical details', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'title' => 'Existing Game',
        'slug' => 'existing-game',
        'release_date' => Date::now()->addMonths(2),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);
    $originalUpdatedAt = $game->updated_at;
    $initialActivityCount = $game->activities()->count();

    $details = [
        'title' => $game->title,
        'slug' => $game->slug,
        'description' => $game->description,
        'cover_image' => $game->cover_image,
        'developer' => $game->developer,
        'publisher' => $game->publisher,
        'genres' => $game->genres,
        'platforms' => $game->platforms,
        'release_date' => $game->release_date?->format('Y-m-d'),
        'release_status' => ReleaseStatus::ComingSoon->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource, $game->id)->handle($resolver, resolve(GameActivityRecorder::class));

    $game->refresh();
    expect($game->updated_at->eq($originalUpdatedAt))->toBeTrue()
        ->and($game->activities()->count())->toBe($initialActivityCount);
});

test('sync updates game and sets last_synced_at when one field differs', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'title' => 'Old Title',
    ]);

    $details = [
        'title' => 'New Title',
        'slug' => $game->slug,
        'description' => $game->description,
        'cover_image' => $game->cover_image,
        'developer' => $game->developer,
        'publisher' => $game->publisher,
        'genres' => $game->genres,
        'platforms' => $game->platforms,
        'release_date' => $game->release_date?->format('Y-m-d'),
        'release_status' => $game->release_status->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource, $game->id)->handle($resolver, resolve(GameActivityRecorder::class));

    $game->refresh();
    expect($game->title)->toBe('New Title')
        ->and($game->last_synced_at)->not->toBeNull();
});

test('sync sets last_synced_at when creating new game', function (): void {
    $details = [
        'title' => 'Brand New Game',
        'slug' => 'brand-new-game',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => '2025-06-01',
        'release_status' => ReleaseStatus::ComingSoon->value,
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn($details);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($this->externalSource)
            ->andReturn($provider);
    });

    new SyncGameJob($this->externalId, $this->externalSource)->handle($resolver, resolve(GameActivityRecorder::class));

    $game = Game::query()
        ->where('external_source', $this->externalSource)
        ->where('external_id', $this->externalId)
        ->first();
    expect($game)->not->toBeNull()
        ->and($game->last_synced_at)->not->toBeNull();
});
