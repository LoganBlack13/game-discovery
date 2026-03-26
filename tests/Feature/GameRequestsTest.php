<?php

declare(strict_types=1);

use App\Models\GameRequest;
use App\Models\User;

it('is publicly accessible without authentication', function (): void {
    $this->get(route('game-requests.index'))->assertOk();
});

it('shows pending game requests', function (): void {
    GameRequest::factory()->create([
        'display_title' => 'Half-Life 3',
        'status' => 'pending',
        'request_count' => 5,
    ]);

    $this->get(route('game-requests.index'))
        ->assertOk()
        ->assertSee('Half-Life 3');
});

it('shows the top 20 pending requests ordered by vote count', function (): void {
    GameRequest::factory()->count(25)->create(['status' => 'pending']);
    GameRequest::factory()->create([
        'display_title' => 'Most Voted Game',
        'status' => 'pending',
        'request_count' => 999,
    ]);

    $response = $this->get(route('game-requests.index'));
    $response->assertOk()->assertSee('Most Voted Game');
});

it('does not show added game requests', function (): void {
    GameRequest::factory()->create([
        'display_title' => 'Already Added Game',
        'status' => 'added',
        'request_count' => 10,
    ]);

    $this->get(route('game-requests.index'))
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
