<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toContain('id', 'name', 'email', 'role');
});

test('isAdmin returns true for admin user', function (): void {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
});

test('isAdmin returns false for regular user', function (): void {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();
});
