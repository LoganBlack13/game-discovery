<?php

declare(strict_types=1);

use App\Models\User;

test('guest cannot access profile', function (): void {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect();
});

test('authenticated user can view profile', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('profile.edit'));

    $response->assertSuccessful();
    $response->assertSee('Profile');
    $response->assertSee($user->name);
});

test('authenticated user can update profile', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);
    $this->actingAs($user);

    $response = $this->patch(route('profile.update'), [
        'name' => 'New Name',
        'username' => $user->username,
        'email' => $user->email,
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->name)->toBe('New Name');
});
