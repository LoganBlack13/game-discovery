<?php

declare(strict_types=1);

use App\Enums\TrackedGameStatus;
use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\GameSession;
use App\Models\JournalEntry;
use App\Models\TrackedGame;
use App\Models\User;
use Livewire\Livewire;

test('the profile shows the companion settings', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('Questlog Companion')
        ->assertSee('Record my play sessions automatically');
});

test('tracking can be turned off and on', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('companion-settings')
        ->assertSet('trackingEnabled', true)
        ->set('trackingEnabled', false)
        ->assertSee('Tracking is off');
    expect($user->fresh()?->companion_tracking_enabled)->toBeFalse();

    $component->set('trackingEnabled', true);
    expect($user->fresh()?->companion_tracking_enabled)->toBeTrue();
});

test('excluded games are listed and can be tracked again', function (): void {
    $user = User::factory()->create();
    $excluded = Game::factory()->create(['title' => 'Excluded Quest']);
    $user->companionExcludedGames()->attach($excluded);

    Livewire::actingAs($user)
        ->test('companion-settings')
        ->assertSee('Excluded Quest')
        ->call('includeGame', $excluded->id)
        ->assertDontSee('Excluded Quest')
        ->assertSee('None. Exclude a game from its page.');

    expect($user->companionExcludedGames()->count())->toBe(0);
});

test('deleting companion data keeps devices and personal tracking', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create();
    $entry = TrackedGame::factory()->playing()->for($user)->create();
    JournalEntry::factory()->for($entry)->create();
    GameSession::factory()->for($entry->game)->create(['companion_device_id' => $device->id]);
    GameExecutableMapping::factory()->create(['user_id' => $user->id]);
    $shared = GameExecutableMapping::factory()->create();
    $user->companionExcludedGames()->attach(Game::factory()->create());
    $otherSession = GameSession::factory()->create();

    Livewire::actingAs($user)
        ->test('companion-settings')
        ->call('deleteAllData')
        ->assertSee('Companion data deleted');

    expect($user->gameSessions()->count())->toBe(0)
        ->and(GameExecutableMapping::query()->pluck('id')->all())->toBe([$shared->id])
        ->and($user->companionExcludedGames()->count())->toBe(0)
        ->and($otherSession->fresh())->not->toBeNull()
        ->and($device->fresh()?->isRevoked())->toBeFalse()
        ->and($entry->fresh()?->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->journalEntries()->count())->toBe(1);
});

test('the companion reads its settings', function (): void {
    $device = CompanionDevice::factory()->create();
    $user = $device->user;
    assert($user instanceof User);
    $first = Game::factory()->create();
    $second = Game::factory()->create();
    $user->companionExcludedGames()->attach([$second->id, $first->id]);
    $user->forceFill(['companion_tracking_enabled' => false])->save();

    $this->withToken(companionToken($device))
        ->getJson(route('api.v1.companion.settings'))
        ->assertSuccessful()
        ->assertExactJson(['data' => ['tracking_enabled' => false, 'excluded_game_ids' => [$first->id, $second->id]]]);
});

test('settings require a companion token', function (): void {
    $this->getJson(route('api.v1.companion.settings'))->assertUnauthorized();
});
