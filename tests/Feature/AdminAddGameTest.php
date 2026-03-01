<?php

declare(strict_types=1);

use App\Contracts\GameDataProvider;
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

    $this->mock(App\Contracts\GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
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

    $this->mock(App\Contracts\GameDataProviderResolver::class, function ($mock) use ($provider, $externalSource): void {
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
