<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Enums\GameActivityType;
use App\Enums\ReleaseStatus;
use App\Jobs\SyncGameJob;
use App\Models\Game;
use App\Models\GameActivity;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->externalId = '12345';
    $this->externalSource = 'rawg';
});

test('sync creates GameActivity when release date changes', function (): void {
    $game = Game::factory()->create([
        'external_id' => $this->externalId,
        'external_source' => $this->externalSource,
        'title' => 'Existing Game',
        'release_date' => Carbon::now()->addMonths(2),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $newReleaseDate = Carbon::now()->addMonth()->format('Y-m-d');
    $this->mock(GameDataProvider::class, function ($mock) use ($game, $newReleaseDate): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn([
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
            ]);
    });

    (new SyncGameJob($this->externalId, $game->id))->handle(app(GameDataProvider::class));

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

    $newReleaseDate = Carbon::now()->addMonths(3)->format('Y-m-d');
    $this->mock(GameDataProvider::class, function ($mock) use ($game, $newReleaseDate): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn([
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
            ]);
    });

    (new SyncGameJob($this->externalId, $game->id))->handle(app(GameDataProvider::class));

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
        'release_date' => Carbon::now()->addWeek(),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $pastDate = Carbon::now()->subDay()->format('Y-m-d');
    $this->mock(GameDataProvider::class, function ($mock) use ($game, $pastDate): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($this->externalId)
            ->andReturn([
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
            ]);
    });

    (new SyncGameJob($this->externalId, $game->id))->handle(app(GameDataProvider::class));

    $activity = GameActivity::query()
        ->where('game_id', $game->id)
        ->where('type', GameActivityType::GameReleased)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->title)->toContain('released');
});
