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
    $response->assertSee('Add game from RAWG', false);
    $response->assertSee('Search RAWG and add a game', false);
});

test('admin can add game to database via Livewire action', function (): void {
    $user = User::factory()->admin()->create();
    $externalId = '12345';
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
        'external_source' => 'rawg',
    ];

    $this->mock(GameDataProvider::class, function ($mock) use ($externalId, $gameDetails): void {
        $mock->shouldReceive('getGameDetails')
            ->once()
            ->with($externalId)
            ->andReturn($gameDetails);
    });

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId);

    expect(Game::query()
        ->where('external_source', 'rawg')
        ->where('external_id', $externalId)
        ->exists())->toBeTrue();

    $game = Game::query()
        ->where('external_source', 'rawg')
        ->where('external_id', $externalId)
        ->first();
    expect($game->title)->toBe('Test Game');
});

test('non-admin cannot add game via Livewire action', function (): void {
    $user = User::factory()->create();
    $externalId = '12345';

    Livewire::actingAs($user)
        ->test('admin-rawg-add-game')
        ->call('addGame', $externalId)
        ->assertForbidden();

    expect(Game::query()
        ->where('external_source', 'rawg')
        ->where('external_id', $externalId)
        ->exists())->toBeFalse();
});
