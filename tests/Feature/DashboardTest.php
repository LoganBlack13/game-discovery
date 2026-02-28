<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

test('guest is redirected to login when visiting dashboard', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated user sees only their tracked games on dashboard', function (): void {
    $user = User::factory()->create();
    $tracked = Game::factory()->create(['title' => 'Tracked Game']);
    $other = Game::factory()->create(['title' => 'Other Game']);
    $user->trackedGames()->attach($tracked);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Tracked Game', false);
    $response->assertDontSee('Other Game', false);
});
