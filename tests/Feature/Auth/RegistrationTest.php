<?php

declare(strict_types=1);

use App\Models\User;

test('registration page is accessible', function (): void {
    $response = $this->get('/register');

    $response->assertSuccessful();
    $response->assertSee('Register');
});

test('user can register with valid data', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertRedirect('/');
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
    ]);
});

test('registration fails with duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'newuser',
        'email' => 'existing@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertInvalid(['email']);
    $this->assertDatabaseCount('users', 1);
});

test('registration fails with duplicate username', function (): void {
    User::factory()->create(['username' => 'taken']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'taken',
        'email' => 'new@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertInvalid(['username']);
    $this->assertDatabaseCount('users', 1);
});

test('registration fails with weak password', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertInvalid(['password']);
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
