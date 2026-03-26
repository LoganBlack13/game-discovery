<?php

declare(strict_types=1);

use App\Enums\GameActivityType;
use App\Enums\ReleaseStatus;
use App\Models\Game;
use App\Models\GameActivity;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;

uses()->group('admin');

test('guest is redirected to login when visiting admin games index', function (): void {
    $response = $this->get(route('admin.games.index'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated non-admin receives 403 when visiting admin games index', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.games.index'));

    $response->assertForbidden();
});

test('authenticated admin can access admin games index', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.games.index'));

    $response->assertOk();
    $response->assertSee('Manage games', false);
    $response->assertSee('Search, filter, and manage games', false);
});

test('admin games list shows games from database', function (): void {
    $game = Game::factory()->create(['title' => 'Listed Game Title']);
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.games-list')
        ->assertSee('Listed Game Title');
});

test('admin can delete game via Livewire action', function (): void {
    $game = Game::factory()->create();
    $admin = User::factory()->admin()->create();
    $gameId = $game->id;

    Livewire::actingAs($admin)
        ->test('admin.games-list')
        ->call('deleteGame', $gameId);

    expect(Game::query()->find($gameId))->toBeNull();
});

test('non-admin cannot delete game via Livewire action', function (): void {
    $game = Game::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('admin.games-list')
        ->call('deleteGame', $game->id)
        ->assertForbidden();

    expect(Game::query()->find($game->id))->not->toBeNull();
});

test('admin can update game via Livewire save', function (): void {
    $game = Game::factory()->create(['title' => 'Original Title']);
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.games-list')
        ->call('openEdit', $game->id)
        ->set('editTitle', 'Updated Title')
        ->set('editReleaseStatus', 'released')
        ->call('save');

    $game->refresh();
    expect($game->title)->toBe('Updated Title');
});

test('non-admin cannot open edit drawer', function (): void {
    $game = Game::factory()->create();
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('admin.games-list');

    $component->call('openEdit', $game->id);

    expect($component->get('editingGameId'))->toBeNull();
});

test('admin update that changes release date creates GameActivity', function (): void {
    $game = Game::factory()->create([
        'release_date' => Date::now()->addMonths(2),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);
    $admin = User::factory()->admin()->create();
    $newDate = Date::now()->addMonth()->format('Y-m-d');

    Livewire::actingAs($admin)
        ->test('admin.games-list')
        ->call('openEdit', $game->id)
        ->set('editReleaseDate', $newDate)
        ->call('save');

    $activity = GameActivity::query()
        ->where('game_id', $game->id)
        ->where('type', GameActivityType::ReleaseDateChanged)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->title)->toBe('Release date changed');
});
