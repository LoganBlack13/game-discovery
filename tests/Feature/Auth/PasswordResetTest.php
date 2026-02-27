<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('forgot password page is accessible', function (): void {
    $response = $this->get('/forgot-password');

    $response->assertSuccessful();
    $response->assertSee('Forgot password');
});

test('password reset link can be requested', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->post('/forgot-password', ['email' => 'user@example.com']);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password can be reset with valid token', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $this->post('/forgot-password', ['email' => 'user@example.com']);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });
    $this->assertNotNull($token);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'user@example.com',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'user@example.com']);
});
