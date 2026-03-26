<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

test('authenticated user can upload a profile photo', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertRedirect();

    $user->refresh();
    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('uploading a new photo deletes the old one', function (): void {
    Storage::fake('public');
    $oldPath = 'profile-photos/old-photo.jpg';
    Storage::disk('public')->put($oldPath, 'old content');
    $user = User::factory()->create(['profile_photo_path' => $oldPath]);
    $this->actingAs($user);

    $this->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'photo' => UploadedFile::fake()->image('new-avatar.jpg'),
    ]);

    Storage::disk('public')->assertMissing($oldPath);
});

test('authenticated user can view recovery codes page without 2FA', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('profile.recovery-codes'));

    $response->assertSuccessful();
});

test('recovery codes page returns codes when 2FA is enabled', function (): void {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('fake-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('profile.recovery-codes'));

    $response->assertSuccessful();
    $response->assertSee('code-one');
});

test('changing email triggers verification notification', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'original@example.com']);
    $this->actingAs($user);

    $this->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $user->username,
        'email' => 'changed@example.com',
    ]);

    $user->refresh();
    expect($user->email)->toBe('changed@example.com')
        ->and($user->email_verified_at)->toBeNull();
    Notification::assertSentTo($user, VerifyEmail::class);
});
