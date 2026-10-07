<?php

declare(strict_types=1);

use App\Enums\GameLauncher;
use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\User;

test('the companion receives its own and shared mappings', function (): void {
    $device = CompanionDevice::factory()->create();
    $game = Game::factory()->create(['title' => 'Hades II']);
    $shared = GameExecutableMapping::factory()->for($game)->create([
        'executable_name' => 'Hades2.exe',
        'launcher' => GameLauncher::Epic,
        'launcher_game_id' => 'abc',
    ]);
    $own = GameExecutableMapping::factory()->create(['user_id' => $device->user_id, 'path_fragment' => 'Games/Celeste']);
    GameExecutableMapping::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->withToken(companionToken($device))
        ->getJson(route('api.v1.companion.mappings.index'))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $shared->id)
        ->assertJsonPath('data.0.game_title', 'Hades II')
        ->assertJsonPath('data.0.executable_name', 'Hades2.exe')
        ->assertJsonPath('data.0.launcher', 'epic')
        ->assertJsonPath('data.0.launcher_game_id', 'abc')
        ->assertJsonPath('data.0.confidence', 'high')
        ->assertJsonPath('data.1.id', $own->id)
        ->assertJsonPath('data.1.path_fragment', 'Games/Celeste');
});

test('a personal mapping replaces the shared mapping of the same executable', function (array $key): void {
    $device = CompanionDevice::factory()->create();
    GameExecutableMapping::factory()->create($key);
    $own = GameExecutableMapping::factory()->create([...$key, 'user_id' => $device->user_id]);

    $this->withToken(companionToken($device))
        ->getJson(route('api.v1.companion.mappings.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id);
})->with([
    'same executable' => [['executable_name' => 'Game.exe', 'path_fragment' => 'Outer Wilds']],
    'same launcher id' => [['launcher' => GameLauncher::Gog, 'launcher_game_id' => '1207659']],
]);

test('mappings require a companion token', function (): void {
    $this->getJson(route('api.v1.companion.mappings.index'))->assertUnauthorized();
});
