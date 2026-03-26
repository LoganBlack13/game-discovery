<?php

declare(strict_types=1);

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('UpdateUserPassword updates password when current password is correct', function (): void {
    $user = User::factory()->create([
        'password' => 'OldPassword1!',
    ]);
    $this->actingAs($user);

    $action = resolve(UpdateUserPassword::class);
    $action->update($user, [
        'current_password' => 'OldPassword1!',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $user->refresh();
    expect(Hash::check('NewPassword1!', $user->password))->toBeTrue();
});

test('UpdateUserPassword throws validation error when current password is wrong', function (): void {
    $user = User::factory()->create([
        'password' => 'CorrectPassword1!',
    ]);
    $this->actingAs($user);

    $action = resolve(UpdateUserPassword::class);

    expect(fn () => $action->update($user, [
        'current_password' => 'WrongPassword1!',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]))->toThrow(ValidationException::class);
});

test('UpdateUserProfileInformation updates name and email', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $action = resolve(UpdateUserProfileInformation::class);
    $action->update($user, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

test('UpdateUserProfileInformation throws validation error when email is not unique', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $action = resolve(UpdateUserProfileInformation::class);

    expect(fn () => $action->update($user, [
        'name' => $user->name,
        'email' => 'taken@example.com',
    ]))->toThrow(ValidationException::class);
});

test('UpdateUserProfileInformation clears email_verified_at when email changes for MustVerifyEmail user', function (): void {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'before@example.com',
        'email_verified_at' => now(),
    ]);

    $action = resolve(UpdateUserProfileInformation::class);
    $action->update($user, [
        'name' => $user->name,
        'email' => 'after@example.com',
    ]);

    $user->refresh();
    expect($user->email)->toBe('after@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('UpdateUserProfileInformation updates name without changing email', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'same@example.com',
    ]);

    $action = resolve(UpdateUserProfileInformation::class);
    $action->update($user, [
        'name' => 'New Name',
        'email' => 'same@example.com',
    ]);

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('same@example.com');
});
