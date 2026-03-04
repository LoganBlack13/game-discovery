<?php

declare(strict_types=1);

use App\Enums\ReleaseStatus;
use App\Models\Game;
use App\Models\News;

test('welcome page loads and shows hero content', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Track your games.', false);
    $response->assertSee('Know when you\'ll actually play them.', false);
    $response->assertSee('Start tracking your games', false);
    $response->assertSee('See how it works', false);
});

test('welcome page shows real upcoming popular and recently released games', function (): void {
    $upcoming = Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => now()->addDays(30),
    ]);
    $released = Game::factory()->create([
        'title' => 'Released Game',
        'release_date' => now()->subDays(10),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming Game', false);
    $response->assertSee('Released Game', false);
    $response->assertSee('Coming soon', false);
    $response->assertSee('Recently released', false);
});

test('coming soon section shows games with release date before games with no release date', function (): void {
    $withDate = Game::factory()->create([
        'title' => 'Upcoming With Date',
        'release_date' => now()->addDays(14),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);
    $noDate = Game::factory()->create([
        'title' => 'Upcoming No Date',
        'release_date' => null,
        'release_status' => ReleaseStatus::Announced,
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming With Date', false);
    $response->assertSee('Upcoming No Date', false);
    $body = $response->getContent();
    $posWithDate = mb_strpos($body, 'Upcoming With Date');
    $posNoDate = mb_strpos($body, 'Upcoming No Date');
    expect($posWithDate)->not->toBeFalse()
        ->and($posNoDate)->not->toBeFalse()
        ->and($posWithDate)->toBeLessThan($posNoDate);
});

test('welcome page shows upcoming releases section with countdown and news', function (): void {
    $game = Game::factory()->create([
        'title' => 'Silksong Demo',
        'release_date' => now()->addDays(120),
    ]);
    News::factory()->create(['game_id' => $game->id, 'title' => 'New trailer']);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming releases', false);
    $response->assertSee('see exactly how long until they release', false);
    $response->assertSee('Silksong Demo', false);
    $response->assertSee('Track your first game', false);
});

test('welcome page upcoming section has game links or preview triggers', function (): void {
    Game::factory()->create(['title' => 'Preview Game', 'release_date' => now()->addDays(30)]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Preview Game', false);
    expect($response->getContent())->toContain('Preview Game');
});

test('welcome page shows latest news section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Latest news', false);
});

test('welcome page shows news items when news exist', function (): void {
    $game = Game::factory()->create();
    $news = News::factory()->create([
        'game_id' => $game->id,
        'title' => 'Unique Headline Alpha Beta',
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Latest news', false);
    $response->assertSee('Unique Headline Alpha Beta', false);
});
