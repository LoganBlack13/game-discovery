<?php

declare(strict_types=1);

use App\Enums\MappingCandidateStatus;
use App\Models\CompanionDevice;
use App\Models\CompanionMappingCandidate;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\GameSession;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('guests are sent to the login page', function (): void {
    $this->get(route('companion.mappings'))->assertRedirect(route('login'));
});

test('the page lists played candidates only', function (): void {
    CompanionMappingCandidate::factory()->for($this->user)->create(['product_name' => 'Played Quest', 'total_seconds' => 3900]);
    CompanionMappingCandidate::factory()->for($this->user)->installedOnly()->create(['product_name' => 'Installed Only']);
    CompanionMappingCandidate::factory()->create(['product_name' => 'Someone Else']);

    $this->actingAs($this->user)
        ->get(route('companion.mappings'))
        ->assertSuccessful()
        ->assertSee('Companion games')
        ->assertSee('Played Quest')
        ->assertSee('played 1 h 05')
        ->assertDontSee('Installed Only')
        ->assertDontSee('Someone Else');
});

test('the empty states are shown', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->assertSee('Nothing to associate')
        ->assertSee('No personal association yet');
});

test('a proposal can be confirmed', function (): void {
    $game = Game::factory()->create(['title' => 'Outer Wilds']);
    $candidate = CompanionMappingCandidate::factory()->for($this->user)->create(['proposed_game_id' => $game->id, 'executable_name' => 'OuterWilds.exe']);

    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->assertSee('It is Outer Wilds')
        ->call('confirmProposal', $candidate->id)
        ->assertSee('Nothing to associate')
        ->assertSee('OuterWilds.exe');

    $mapping = GameExecutableMapping::query()->sole();
    expect($mapping->game_id)->toBe($game->id)
        ->and($mapping->user_id)->toBe($this->user->id)
        ->and($mapping->validated_by_user)->toBeTrue()
        ->and($candidate->fresh()?->status)->toBe(MappingCandidateStatus::Mapped);
});

test('another game can be searched and chosen', function (): void {
    Game::factory()->create(['title' => 'Celeste']);
    $chosen = Game::factory()->create(['title' => 'Celeste Classic']);
    $candidate = CompanionMappingCandidate::factory()->for($this->user)->create(['product_name' => 'Celeste']);

    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('startSearch', $candidate->id)
        ->assertSet('search', 'Celeste')
        ->assertSee('Celeste Classic')
        ->call('chooseGame', $chosen->id)
        ->assertSet('searchingCandidateId', null);

    expect(GameExecutableMapping::query()->sole()->game_id)->toBe($chosen->id);
});

test('the search finds a sequel whatever the way its number is written', function (): void {
    Game::factory()->create(['title' => 'Graveyard Keeper II']);
    Game::factory()->create(['title' => 'Baldur\'s Gate 3']);
    $candidate = CompanionMappingCandidate::factory()->for($this->user)->create(['product_name' => 'Graveyard Keeper 2']);

    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('startSearch', $candidate->id)
        ->assertSet('search', 'Graveyard Keeper 2')
        ->assertSee('Graveyard Keeper II')
        ->set('search', 'gate iii')
        ->assertSee('Baldur\'s Gate 3')
        ->assertDontSee('Graveyard Keeper II');
});

test('searching with fewer than two characters lists nothing', function (): void {
    Game::factory()->create(['title' => 'A']);
    $candidate = CompanionMappingCandidate::factory()->for($this->user)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('startSearch', $candidate->id)
        ->set('search', 'A');

    expect($component->instance()->searchResults)->toBeEmpty();

    $component->set('search', 'Zzzz')->assertSee('No game found');
});

test('a candidate can be ignored and restored', function (): void {
    $candidate = CompanionMappingCandidate::factory()->for($this->user)->create(['product_name' => 'Blender']);

    $component = Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('ignore', $candidate->id)
        ->assertSee('Not games');
    expect($candidate->fresh()?->status)->toBe(MappingCandidateStatus::Ignored);

    $component->call('restore', $candidate->id)->assertDontSee('Not games');
    expect($candidate->fresh()?->status)->toBe(MappingCandidateStatus::Pending);
});

test('a mapping can point to another game and bring its past sessions', function (bool $move): void {
    $device = CompanionDevice::factory()->for($this->user)->create();
    $old = Game::factory()->create();
    $new = Game::factory()->create(['title' => 'Right Game']);
    $mapping = GameExecutableMapping::factory()->for($old)->create(['user_id' => $this->user->id]);
    $session = GameSession::factory()->for($old)->create(['companion_device_id' => $device->id, 'game_executable_mapping_id' => $mapping->id]);

    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('startReassign', $mapping->id)
        ->set('movePastSessions', $move)
        ->set('search', 'Right')
        ->call('chooseGame', $new->id)
        ->assertSet('reassigningMappingId', null);

    expect($mapping->fresh()?->game_id)->toBe($new->id)
        ->and($session->fresh()?->game_id)->toBe($move ? $new->id : $old->id);
})->with(['move past sessions' => true, 'future sessions only' => false]);

test('a mapping can be removed', function (): void {
    $mapping = GameExecutableMapping::factory()->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('deleteMapping', $mapping->id)
        ->assertSee('No personal association yet');

    expect($mapping->fresh())->toBeNull();
});

test('the data of other users and shared mappings cannot be changed', function (string $action, Closure $target): void {
    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call($action, $target()->id)
        ->assertForbidden();
})->with([
    'confirm' => ['confirmProposal', fn (): CompanionMappingCandidate => CompanionMappingCandidate::factory()->create()],
    'ignore' => ['ignore', fn (): CompanionMappingCandidate => CompanionMappingCandidate::factory()->create()],
    'reassign shared' => ['startReassign', fn (): GameExecutableMapping => GameExecutableMapping::factory()->create()],
    'delete shared' => ['deleteMapping', fn (): GameExecutableMapping => GameExecutableMapping::factory()->create()],
]);

test('a reassignment is refused once the mapping is no longer the user\'s', function (): void {
    $mapping = GameExecutableMapping::factory()->create(['user_id' => $this->user->id]);
    $component = Livewire::actingAs($this->user)->test('pages::companion-mappings')->call('startReassign', $mapping->id);
    $mapping->forceFill(['user_id' => null])->save();

    $component->call('chooseGame', Game::factory()->create()->id)->assertForbidden();
});

test('choosing a game without a search in progress does nothing', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::companion-mappings')
        ->call('chooseGame', Game::factory()->create()->id)
        ->assertHasNoErrors();

    expect(GameExecutableMapping::query()->count())->toBe(0);
});

test('the profile links to the page and counts the games to associate', function (): void {
    CompanionMappingCandidate::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('companion-settings')
        ->assertSee('Companion games')
        ->assertSee('1 to associate')
        ->set('suggestUnknownGames', false);

    expect($this->user->fresh()?->companion_suggest_unknown_games)->toBeFalse();
});
