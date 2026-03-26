<?php

declare(strict_types=1);

use App\Models\User;

uses()->group('admin');

test('guest is redirected to login when visiting admin', function (): void {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated non-admin receives 403 when visiting admin', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('authenticated admin can access admin dashboard', function (): void {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin', false);
    $response->assertSee('Administrator actions', false);
});
