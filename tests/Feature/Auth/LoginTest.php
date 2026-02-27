<?php

declare(strict_types=1);

use App\Models\User;

test('login page is accessible', function (): void {
    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertSee('Log in');
});

test('user can log in with valid credentials', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
        'remember' => false,
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function (): void {
    User::factory()->create(['email' => 'user@example.com']);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('authenticated user can log out', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
