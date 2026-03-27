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

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->call('select', 'abc123')
        ->assertSet('selectedSeed', 'abc123');
});

test('saving persists the selected seed to the database', function (): void {
    $user = User::factory()->create(['avatar_seed' => null]);

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->call('select', 'new-seed')
        ->call('save');

    expect($user->fresh()->avatar_seed)->toBe('new-seed');
});

test('saving dispatches avatar-saved event', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('avatar-picker')
        ->call('select', 'some-seed')
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
