<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;

uses()->group('admin');

test('guest cannot access news enrichment progress', function (): void {
    $response = $this->getJson(route('admin.news-enrichment.progress', ['run_id' => 'test-run-123']));

    $response->assertUnauthorized();
});

test('admin can get progress when run_id exists in cache', function (): void {
    $admin = User::factory()->admin()->create();
    $runId = 'progress-test-run';
    $payload = [
        'status' => 'running',
        'current_feed_name' => 'GameSpot',
        'current_feed_url' => 'https://example.com/feed',
        'feeds_total' => 12,
        'feeds_done' => 3,
        'last_matched' => [['game_title' => 'Elden Ring', 'news_title' => 'Elden Ring DLC']],
        'created_count' => 5,
        'error' => null,
    ];
    Cache::put("news_enrichment:progress:{$runId}", $payload, 3600);

    $response = $this->actingAs($admin)->getJson(route('admin.news-enrichment.progress', ['run_id' => $runId]));

    $response->assertOk();
    $response->assertJsonPath('status', 'running');
    $response->assertJsonPath('current_feed_name', 'GameSpot');
    $response->assertJsonPath('feeds_done', 3);
    $response->assertJsonPath('created_count', 5);
});

test('admin gets not_found when run_id has no cache entry', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson(route('admin.news-enrichment.progress', ['run_id' => 'nonexistent-run']));

    $response->assertNotFound();
});

test('non-admin cannot access news enrichment progress', function (): void {
    $user = User::factory()->create();
    Cache::put('news_enrichment:progress:some-run', ['status' => 'running'], 3600);

    $response = $this->actingAs($user)->getJson(route('admin.news-enrichment.progress', ['run_id' => 'some-run']));

    $response->assertForbidden();
});
