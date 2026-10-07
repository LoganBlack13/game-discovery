<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameSession;
use App\Models\SavedMoment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->session = GameSession::factory()->create();
    $this->user = User::query()->findOrFail($this->session->user_id);
    $this->game = Game::query()->findOrFail($this->session->game_id);
});

function momentFor(mixed $test, array $attributes = []): SavedMoment
{
    $moment = SavedMoment::factory()->create(['game_session_id' => $test->session->id, ...$attributes]);
    Storage::disk('local')->put($moment->path, 'image');

    return $moment;
}

test('the gallery is hidden without moments', function (): void {
    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->assertDontSee('Moments');
});

test('the twelve latest moments are shown until asked', function (): void {
    foreach (range(1, 13) as $minutes) {
        momentFor($this, ['captured_at' => now()->subMinutes($minutes)]);
    }
    SavedMoment::factory()->create();

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->assertSee('13 moments saved with Questlog Companion')
        ->assertCount('moments', 12)
        ->call('showAllMoments')
        ->assertCount('moments', 13)
        ->assertDontSee('Show all 13 moments');
});

test('a caption can be added, changed and cleared', function (): void {
    $moment = momentFor($this);

    $component = Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->call('editCaption', $moment->id)
        ->set('caption', '  Final boss down  ')
        ->call('saveCaption')
        ->assertHasNoErrors()
        ->assertSee('Final boss down');
    expect($moment->fresh()?->caption)->toBe('Final boss down');

    $component->call('editCaption', $moment->id)
        ->assertSet('caption', 'Final boss down')
        ->set('caption', '')
        ->call('saveCaption');
    expect($moment->fresh()?->caption)->toBeNull();
});

test('captions are limited to 280 characters', function (): void {
    $moment = momentFor($this);

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->call('editCaption', $moment->id)
        ->set('caption', str_repeat('a', 281))
        ->call('saveCaption')
        ->assertHasErrors(['caption' => 'max']);
});

test('saving without editing does nothing and editing can be cancelled', function (): void {
    $moment = momentFor($this);

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->call('saveCaption')
        ->call('editCaption', $moment->id)
        ->call('cancelCaption')
        ->assertSet('editingId', null);
});

test('a moment and its files can be deleted', function (): void {
    $moment = momentFor($this, ['thumbnail_path' => 'moments/thumb.jpg']);
    Storage::disk('local')->put('moments/thumb.jpg', 'thumb');

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->call('editCaption', $moment->id)
        ->call('deleteMoment', $moment->id)
        ->assertSet('editingId', null)
        ->assertDontSee('Moments');

    expect($moment->fresh())->toBeNull();
    Storage::disk('local')->assertMissing($moment->path);
    Storage::disk('local')->assertMissing('moments/thumb.jpg');
});

test('the moments of another user cannot be changed', function (string $action): void {
    $foreign = SavedMoment::factory()->create();

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->call($action, $foreign->id)
        ->assertForbidden();
})->with(['editCaption', 'deleteMoment']);

test('a forged caption edit is refused', function (): void {
    $foreign = SavedMoment::factory()->create();

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => $this->game])
        ->set('editingId', $foreign->id)
        ->set('caption', 'hacked')
        ->call('saveCaption')
        ->assertForbidden();
});

test('deleting a session deletes its moments', function (): void {
    $moment = momentFor($this);
    $kept = SavedMoment::factory()->create();

    Livewire::actingAs($this->user)
        ->test('game-sessions', ['game' => $this->game])
        ->call('deleteSession', $this->session->id)
        ->assertDispatched('companion-moments-changed');

    expect($moment->fresh())->toBeNull()
        ->and($kept->fresh())->not->toBeNull();
    Storage::disk('local')->assertMissing($moment->path);
});

test('the game page shows the gallery', function (): void {
    momentFor($this, ['caption' => 'Sunset over the bay']);

    $this->actingAs($this->user)
        ->get(route('games.show', $this->game))
        ->assertSuccessful()
        ->assertSee('Sunset over the bay')
        ->assertSee(route('moments.image', ['moment' => SavedMoment::query()->firstOrFail(), 'size' => 'thumbnail']), false);
});

test('deleting all companion data deletes moments and their files', function (): void {
    $moment = momentFor($this);
    $other = SavedMoment::factory()->create();
    Storage::disk('local')->put($other->path, 'image');

    Livewire::actingAs($this->user)
        ->test('companion-settings')
        ->call('deleteAllData');

    expect($moment->fresh())->toBeNull()
        ->and($other->fresh())->not->toBeNull();
    Storage::disk('local')->assertMissing($moment->path);
    Storage::disk('local')->assertExists($other->path);
});

test('deleting all companion data deletes the files of every disk, thumbnails included', function (): void {
    Storage::fake('s3');
    $remote = momentFor($this, ['disk' => 's3', 'thumbnail_path' => 'moments/remote_thumb.jpg']);
    Storage::disk('s3')->put($remote->path, 'image');
    Storage::disk('s3')->put('moments/remote_thumb.jpg', 'thumb');
    $local = momentFor($this);

    Livewire::actingAs($this->user)
        ->test('companion-settings')
        ->call('deleteAllData');

    expect($remote->fresh())->toBeNull()
        ->and($local->fresh())->toBeNull();
    Storage::disk('s3')->assertMissing($remote->path);
    Storage::disk('s3')->assertMissing('moments/remote_thumb.jpg');
    Storage::disk('local')->assertMissing($local->path);
});

test('moment settings can be changed', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('companion-settings')
        ->assertSet('momentHotkey', 'Ctrl+Shift+F9')
        ->assertSee('0 MB used of 1024 MB')
        ->set('momentHotkey', ' Alt+F10 ')
        ->call('saveMomentHotkey')
        ->assertHasNoErrors()
        ->assertSee('Saved.')
        ->set('momentSound', false)
        ->set('momentUpload', false)
        ->assertSee('Moments stay on your computer only.');

    $user->refresh();
    expect($user->companion_moment_hotkey)->toBe('Alt+F10')
        ->and($user->companion_moment_sound)->toBeFalse()
        ->and($user->companion_moment_upload)->toBeFalse();

    $component->set('momentHotkey', 'F9')->call('saveMomentHotkey')->assertHasErrors(['momentHotkey' => 'regex']);
    $component->set('momentHotkey', '')->call('saveMomentHotkey')->assertHasErrors(['momentHotkey' => 'required']);
    expect($user->fresh()?->companion_moment_hotkey)->toBe('Alt+F10');
});

test('valid shortcuts', function (string $hotkey): void {
    expect(preg_match(App\Http\Requests\Companion\UpdateCompanionMomentsRequest::HOTKEY_PATTERN, $hotkey))->toBe(1);
})->with(['Ctrl+Shift+F9', 'Win+PrintScreen', 'Ctrl+Alt+S', 'Shift+F24', 'Alt+0']);

test('invalid shortcuts', function (string $hotkey): void {
    expect(preg_match(App\Http\Requests\Companion\UpdateCompanionMomentsRequest::HOTKEY_PATTERN, $hotkey))->toBe(0);
})->with(['F9', 'Ctrl+', 'Ctrl+Shift+F25', 'ctrl+s', 'Ctrl+Space', 'Ctrl+Shift+F9+X']);

test('the used storage is shown in megabytes', function (): void {
    momentFor($this, ['size_bytes' => 5 * 1024 * 1024 + 1]);

    Livewire::actingAs($this->user)
        ->test('companion-settings')
        ->assertSee('6 MB used of 1024 MB');
});

test('the gallery ignores other games', function (): void {
    momentFor($this);

    Livewire::actingAs($this->user)
        ->test('game-moments', ['game' => Game::factory()->create()])
        ->assertDontSee('Moments');
});
