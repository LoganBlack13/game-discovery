<?php

declare(strict_types=1);

use App\Models\CompanionDevice;
use App\Models\GameSession;
use App\Models\TrackedGame;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('games in progress show their last session or that they are being played', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create();
    $finished = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => now()->subDay()]);
    $live = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => now()]);
    $untouched = TrackedGame::factory()->playing()->for($user)->create(['last_activity_at' => now()->subDays(3)]);

    GameSession::factory()->for($finished->game)->ended()->create([
        'companion_device_id' => $device->id,
        'started_at' => now()->subDays(5),
        'last_heartbeat_at' => now()->subDays(5)->addHour(),
        'active_seconds' => 3000,
    ]);
    GameSession::factory()->for($finished->game)->ended()->create([
        'companion_device_id' => $device->id,
        'started_at' => now()->subDay(),
        'last_heartbeat_at' => now()->subDay()->addHour(),
        'active_seconds' => 3900,
    ]);
    GameSession::factory()->for($live->game)->create([
        'companion_device_id' => $device->id,
        'started_at' => now()->subMinutes(20),
        'last_heartbeat_at' => now()->subMinute(),
    ]);

    Livewire::actingAs($user)
        ->test('dashboard-game-list')
        ->assertSee('Playing now')
        ->assertSee('Last session 1 day ago · 1 h 05')
        ->assertDontSee('50 min')
        ->assertSee('Last played 3 days ago');

    expect($untouched->game_id)->not->toBeNull();
});

test('the last sessions of the games in progress are loaded in one query', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create();
    TrackedGame::factory()->playing()->for($user)->count(4)->create()->each(function (TrackedGame $entry) use ($device): void {
        GameSession::factory()->for($entry->game)->ended()->count(2)->create(['companion_device_id' => $device->id]);
    });

    $sessionQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$sessionQueries): void {
        if (str_contains($query->sql, 'game_sessions')) {
            $sessionQueries++;
        }
    });

    Livewire::actingAs($user)->test('dashboard-game-list')->assertSee('Last session');

    expect($sessionQueries)->toBe(1);
});
