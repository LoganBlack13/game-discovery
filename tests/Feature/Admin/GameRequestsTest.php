<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;

uses()->group('admin');

test('guest cannot access game requests page', function (): void {
    $response = $this->get(route('admin.game-requests'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('login');
});

test('authenticated non-admin receives 403 when visiting game requests page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.game-requests'));

    $response->assertForbidden();
});

test('admin can access game requests page', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.game-requests'));

    $response->assertOk();
    $response->assertSee('Game requests', false);
    $response->assertSee('Run processor', false);
});

test('guest cannot access game requests progress endpoint', function (): void {
    $response = $this->getJson(route('admin.game-requests.progress', ['run_id' => 'test-run-123']));

    $response->assertUnauthorized();
});

test('admin can get progress when run_id exists in cache', function (): void {
    $admin = User::factory()->admin()->create();
    $runId = 'progress-test-run';
    $payload = [
        'status' => 'completed',
        'current_title' => null,
        'processed' => 2,
        'added' => 1,
        'error' => null,
    ];
    Cache::put('game_requests:progress:'.$runId, $payload, 3600);

    $response = $this->actingAs($admin)->getJson(route('admin.game-requests.progress', ['run_id' => $runId]));

    $response->assertOk();
    $response->assertJsonPath('status', 'completed');
    $response->assertJsonPath('processed', 2);
    $response->assertJsonPath('added', 1);
});

test('admin gets 404 when run_id has no cache entry', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson(route('admin.game-requests.progress', ['run_id' => 'nonexistent-run']));

    $response->assertNotFound();
});

test('non-admin cannot access game requests progress endpoint', function (): void {
    $user = User::factory()->create();
    Cache::put('game_requests:progress:some-run', ['status' => 'running'], 3600);

    $response = $this->actingAs($user)->getJson(route('admin.game-requests.progress', ['run_id' => 'some-run']));

    $response->assertForbidden();
});
