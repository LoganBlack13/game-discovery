<?php

declare(strict_types=1);

use App\Models\GameRequest;
use App\Models\User;

it('redirects unauthenticated users to login', function (): void {
    $this->get(route('game-requests.index'))->assertRedirectToRoute('login');
});

it('shows pending game requests', function (): void {
    $user = User::factory()->create();

    GameRequest::factory()->create([
        'display_title' => 'Half-Life 3',
        'status' => 'pending',
        'request_count' => 5,
    ]);

    $this->actingAs($user)
        ->get(route('game-requests.index'))
        ->assertOk()
        ->assertSee('Half-Life 3');
});

it('shows the top 20 pending requests ordered by vote count', function (): void {
    $user = User::factory()->create();

    GameRequest::factory()->count(25)->create(['status' => 'pending']);
    GameRequest::factory()->create([
        'display_title' => 'Most Voted Game',
        'status' => 'pending',
        'request_count' => 999,
    ]);

    $this->actingAs($user)
        ->get(route('game-requests.index'))
        ->assertOk()
        ->assertSee('Most Voted Game');
});

it('does not show added game requests', function (): void {
    $user = User::factory()->create();

    GameRequest::factory()->create([
        'display_title' => 'Already Added Game',
        'status' => 'added',
        'request_count' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('game-requests.index'))
        ->assertOk()
        ->assertDontSee('Already Added Game');
});

it('shows the request form to authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('game-requests.index'))
        ->assertOk()
        ->assertSee('Request game');
});

it('pre-fills the title field from the title query parameter', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('game-requests.index', ['title' => 'Frostpunk 3']))
        ->assertOk()
        ->assertSee('Frostpunk 3');
});
