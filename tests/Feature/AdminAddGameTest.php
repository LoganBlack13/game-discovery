<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver;
use App\Enums\ReleaseStatus;
use App\Models\Game;
use App\Models\User;
use Livewire\Livewire;

uses()->group('admin');

test('guest is redirected to login when visiting add-game page', function (): void {
    $response = $this->get(route('admin.add-game'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated non-admin receives 403 when visiting add-game page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.add-game'));

    $response->assertForbidden();
});

test('authenticated admin can access add-game page', function (): void {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get(route('admin.add-game'));

    $response->assertOk();
    $response->assertSee('Add game', false);
    $response->assertSee('Search RAWG and IGDB', false);
});

test('admin can add game to database via Livewire action', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '12345';
    $externalSource = 'rawg';
    $gameDetails = [
        'title' => 'Test Game',
        'slug' => 'test-game',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => '2024-01-15',
        'release_status' => ReleaseStatus::Released->value,
        'external_id' => $externalId,
        'external_source' => $externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($externalId, $gameDetails): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($externalId)
            ->andReturn($gameDetails);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($externalSource)
            ->andReturn($provider);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId, $externalSource);

    expect(Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->exists())->toBeTrue();

    $game = Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->first();
    expect($game->title)->toBe('Test Game');
});

test('non-admin cannot add game via Livewire action', function (): void {
    $user = User::factory()->create();
    $externalId = '12345';
    $externalSource = 'rawg';

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId, $externalSource)
        ->assertForbidden();

    expect(Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->exists())->toBeFalse();
});

test('admin can add IGDB-sourced game to database via Livewire action', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '98765';
    $externalSource = 'igdb';
    $gameDetails = [
        'title' => 'IGDB Test Game',
        'slug' => 'igdb-test-game',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => '2024-06-01',
        'release_status' => ReleaseStatus::Released->value,
        'external_id' => $externalId,
        'external_source' => $externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($externalId, $gameDetails): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($externalId)
            ->andReturn($gameDetails);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($externalSource)
            ->andReturn($provider);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId, $externalSource);

    expect(Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->exists())->toBeTrue();

    $game = Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->first();
    expect($game->title)->toBe('IGDB Test Game');
});

test('add-game page shows RAWG and IGDB column headings and results per source', function (): void {
    $user = User::factory()->admin()->create();
    $rawgResults = [
        [
            'title' => 'RAWG Only Game',
            'slug' => 'rawg-only-game',
            'description' => null,
            'cover_image' => null,
            'developer' => null,
            'publisher' => null,
            'genres' => [],
            'platforms' => [],
            'release_date' => null,
            'release_status' => ReleaseStatus::Announced->value,
            'external_id' => '111',
            'external_source' => 'rawg',
        ],
    ];
    $igdbResults = [
        [
            'title' => 'IGDB Only Game',
            'slug' => 'igdb-only-game',
            'description' => null,
            'cover_image' => null,
            'developer' => null,
            'publisher' => null,
            'genres' => [],
            'platforms' => [],
            'release_date' => null,
            'release_status' => ReleaseStatus::Announced->value,
            'external_id' => '222',
            'external_source' => 'igdb',
        ],
    ];

    $rawgProvider = $this->mock(GameDataProvider::class, function ($mock) use ($rawgResults): void {
        $mock->shouldReceive('search')->andReturn($rawgResults);
    });
    $igdbProvider = $this->mock(GameDataProvider::class, function ($mock) use ($igdbResults): void {
        $mock->shouldReceive('search')->andReturn($igdbResults);
    });

    $resolver = $this->mock(GameDataProviderResolver::class, function ($mock) use ($rawgProvider, $igdbProvider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($rawgProvider);
        $mock->shouldReceive('resolve')->with('igdb')->andReturn($igdbProvider);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->set('query', 'test')
        ->assertSee('RAWG')
        ->assertSee('IGDB')
        ->assertSee('RAWG Only Game')
        ->assertSee('IGDB Only Game');
});

test('update existing game via add-game does not change updated_at when details unchanged', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '123';
    $externalSource = 'rawg';
    $game = Game::factory()->create([
        'external_id' => $externalId,
        'external_source' => $externalSource,
        'title' => 'Unchanged Game',
        'slug' => 'unchanged-game',
    ]);
    $originalUpdatedAt = $game->updated_at;
    $originalLastSyncedAt = $game->last_synced_at;

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
        'release_status' => $game->release_status->value,
        'external_id' => $externalId,
        'external_source' => $externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($externalId, $details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($externalId)
            ->andReturn($details);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($externalSource)
            ->andReturn($provider);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId, $externalSource);

    $game->refresh();
    expect($game->updated_at->eq($originalUpdatedAt))->toBeTrue()
        ->and($game->last_synced_at)->toBe($originalLastSyncedAt);
});

test('update existing game via add-game updates title and sets last_synced_at when details changed', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '456';
    $externalSource = 'rawg';
    Game::factory()->create([
        'external_id' => $externalId,
        'external_source' => $externalSource,
        'title' => 'Old Title',
    ]);

    $details = [
        'title' => 'New Title From Provider',
        'slug' => 'old-title',
        'description' => null,
        'cover_image' => null,
        'developer' => null,
        'publisher' => null,
        'genres' => [],
        'platforms' => [],
        'release_date' => null,
        'release_status' => ReleaseStatus::Announced->value,
        'external_id' => $externalId,
        'external_source' => $externalSource,
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($externalId, $details): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($externalId)
            ->andReturn($details);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->with($externalSource)
            ->andReturn($provider);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId, $externalSource);

    $game = Game::query()
        ->where('external_source', $externalSource)
        ->where('external_id', $externalId)
        ->first();
    expect($game->title)->toBe('New Title From Provider')
        ->and($game->last_synced_at)->not->toBeNull();
});

test('add-game shows Already in database and Update for game already in DB', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '789';
    $externalSource = 'rawg';
    Game::factory()->create([
        'external_id' => $externalId,
        'external_source' => $externalSource,
        'title' => 'Existing Game',
    ]);

    $searchResults = [
        [
            'title' => 'Existing Game',
            'slug' => 'existing-game',
            'description' => null,
            'cover_image' => null,
            'developer' => null,
            'publisher' => null,
            'genres' => [],
            'platforms' => [],
            'release_date' => null,
            'release_status' => ReleaseStatus::Announced->value,
            'external_id' => $externalId,
            'external_source' => $externalSource,
        ],
    ];

    $provider = $this->mock(GameDataProvider::class, function ($mock) use ($searchResults): void {
        $mock->shouldReceive('search')->andReturn($searchResults);
    });

    $this->mock(GameDataProviderResolver::class, function ($mock) use ($provider): void {
        $mock->shouldReceive('resolve')->with('rawg')->andReturn($provider);
        $mock->shouldReceive('resolve')->with('igdb')->andReturn($this->mock(GameDataProvider::class, fn ($m) => $m->shouldReceive('search')->andReturn([])));
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->set('query', 'Existing')
        ->assertSee('Already in database')
        ->assertSee('Update');
});
