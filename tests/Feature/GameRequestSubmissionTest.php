<?php

declare(strict_types=1);

use App\Models\GameRequest;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can submit a game request', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('game-request-card')
        ->set('title', 'Elden Ring')
        ->call('submit');

    expect(GameRequest::query()->count())->toBe(1)
        ->and(GameRequest::query()->first()->normalized_title)->toBe('elden ring')
        ->and(GameRequest::query()->first()->display_title)->toBe('Elden Ring')
        ->and(GameRequest::query()->first()->votes()->count())->toBe(1);
});

test('same user submitting same title again still has one vote', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('game-request-card')
        ->set('title', 'Elden Ring')
        ->call('submit')
        ->call('submit');

    $request = GameRequest::query()->where('normalized_title', 'elden ring')->first();
    expect($request)->not->toBeNull()
        ->and($request->votes()->count())->toBe(1)
        ->and($request->request_count)->toBe(1);
});

test('second user submitting same title increments request count', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Livewire::actingAs($user1)
        ->test('game-request-card')
        ->set('title', 'Hades')
        ->call('submit');

    Livewire::actingAs($user2)
        ->test('game-request-card')
        ->set('title', 'Hades')
        ->call('submit');

    $request = GameRequest::query()->where('normalized_title', 'hades')->first();
    expect($request)->not->toBeNull()
        ->and($request->votes()->count())->toBe(2)
        ->and($request->request_count)->toBe(2);
});

test('guest cannot submit and sees message', function (): void {
    Livewire::test('game-request-card')
        ->set('title', 'Elden Ring')
        ->call('submit')
        ->assertSet('success', false)
        ->assertSet('feedback', 'You must be signed in to request a game.');

    expect(GameRequest::query()->count())->toBe(0);
});

test('validation rejects empty title', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('game-request-card')
        ->set('title', '')
        ->call('submit')
        ->assertHasErrors(['title' => 'required']);

    expect(GameRequest::query()->count())->toBe(0);
});
