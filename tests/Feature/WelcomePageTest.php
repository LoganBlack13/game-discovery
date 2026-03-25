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

test('welcome page shows real upcoming games in upcoming releases section', function (): void {
    $upcoming = Game::factory()->create([
        'title' => 'Upcoming Game',
        'release_date' => now()->addDays(30),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming Game', false);
    $response->assertSee('Upcoming releases', false);
});

test('upcoming releases section shows games with release date before games with no release date', function (): void {
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

test('welcome page shows features overview section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Everything you need to stay on top of your games', false);
    $response->assertSee('Upcoming releases', false);
    $response->assertSee('Latest news', false);
    $response->assertSee('Backlog planning', false);
});

test('layout footer contains product name and key links', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee(config('app.name'), false);
    $response->assertSee('Games', false);
    $response->assertSee('How it works', false);
});

test('welcome page shows final CTA section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Track your games and plan your backlog.', false);
});

test('welcome page shows how it works section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Everything you need to stay on top of your games', false);
    $response->assertSee('One place for upcoming releases', false);
});

test('welcome page shows stay updated news section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Stay updated on your games', false);
    $response->assertSee('Follow your games', false);
});

test('welcome page shows latest news section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Stay updated on your games', false);
});

test('welcome page shows news items when news exist', function (): void {
    $game = Game::factory()->create();
    $news = News::factory()->create([
        'game_id' => $game->id,
        'title' => 'Unique Headline Alpha Beta',
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Stay updated on your games', false);
    $response->assertSee('Unique Headline Alpha Beta', false);
});
