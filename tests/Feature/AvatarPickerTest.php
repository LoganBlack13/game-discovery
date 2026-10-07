<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

test('guest cannot access avatar picker component', function (): void {
    $this->get(route('profile.edit'))->assertRedirect();
});

test('avatar picker initialises with user saved seed', function (): void {
    $user = User::factory()->create(['avatar_seed' => 'my-seed']);

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->assertSet('selectedSeed', 'my-seed');
});

test('avatar picker initialises with null when no seed saved', function (): void {
    $user = User::factory()->create(['avatar_seed' => null]);

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->assertSet('selectedSeed', null);
});

test('selecting a seed updates selected seed', function (): void {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('avatar-picker');
    $seed = $component->get('seeds')[0];

    $component->call('select', $seed)
        ->assertSet('selectedSeed', $seed);
});

test('selecting a seed outside the offered seeds is ignored', function (): void {
    $user = User::factory()->create(['avatar_seed' => null]);

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->call('select', 'not-offered')
        ->assertSet('selectedSeed', null);
});

test('saving persists the selected seed to the database', function (): void {
    $user = User::factory()->create(['avatar_seed' => null]);
    $component = Livewire::actingAs($user)->test('avatar-picker');
    $seed = $component->get('seeds')[3];

    $component->call('select', $seed)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->avatar_seed)->toBe($seed);
});

test('saving rejects a seed that was not offered', function (string $seed): void {
    $user = User::factory()->create(['avatar_seed' => null]);

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->set('selectedSeed', $seed)
        ->call('save')
        ->assertHasErrors(['selectedSeed'])
        ->assertNotDispatched('avatar-saved');

    expect($user->fresh()->avatar_seed)->toBeNull();
})->with([
    'arbitrary string' => 'attacker-seed',
    'oversized string' => str_repeat('a', 5000),
    'another user seed' => mb_substr(md5('other-user-avatar-0'), 0, 10),
]);

test('saving dispatches avatar-saved event', function (): void {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('avatar-picker');

    $component->call('select', $component->get('seeds')[0])
        ->call('save')
        ->assertDispatched('avatar-saved');
});

test('seeds computed property returns configured count of seeds', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('avatar-picker');

    expect($component->get('seeds'))->toHaveCount(config('avatar.count', 12));
});

test('seeds are deterministic for the same user', function (): void {
    $user = User::factory()->create();

    $first = Livewire::actingAs($user)->test('avatar-picker')->get('seeds');
    $second = Livewire::actingAs($user)->test('avatar-picker')->get('seeds');

    expect($first)->toBe($second);
});
