<?php

declare(strict_types=1);

use App\Enums\GameLauncher;
use App\Services\IgdbGameDataProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.igdb.client_id', 'test-client-id');
    Config::set('services.igdb.client_secret', 'test-client-secret');
});

function fakeIgdbExternalGames(array $rows, int $status = 200): void
{
    Http::fake([
        'https://id.twitch.tv/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        'https://api.igdb.com/v4/external_games' => Http::response($rows, $status),
    ]);
}

test('a store identifier resolves to its IGDB game', function (): void {
    fakeIgdbExternalGames([['id' => 9, 'game' => 1942, 'uid' => '1207659']]);

    expect(new IgdbGameDataProvider()->findGameIdByExternalUid(GameLauncher::Gog, '1207659'))->toBe(1942);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.igdb.com/v4/external_games'
        && str_contains($request->body(), 'where uid = "1207659" & external_game_source = (5)'));
});

test('xbox identifiers are looked up in every Microsoft store source', function (): void {
    fakeIgdbExternalGames([['game' => 7]]);

    new IgdbGameDataProvider()->findGameIdByExternalUid(GameLauncher::Xbox, '9NBLGGH4R315');

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'external_game_source = (11,31,54)'));
});

test('an identifier shared by several games is ambiguous and cached', function (): void {
    fakeIgdbExternalGames([['game' => 1], ['game' => 2], ['game' => 1]]);
    $provider = new IgdbGameDataProvider;

    expect($provider->findGameIdByExternalUid(GameLauncher::Epic, 'abc'))->toBeNull()
        ->and($provider->findGameIdByExternalUid(GameLauncher::Epic, 'abc'))->toBeNull();

    Http::assertSentCount(2);
});

test('answers are cached for thirty days', function (): void {
    fakeIgdbExternalGames([['game' => 1942]]);
    $provider = new IgdbGameDataProvider;
    $provider->findGameIdByExternalUid(GameLauncher::Gog, '1');

    expect($provider->findGameIdByExternalUid(GameLauncher::Gog, '1'))->toBe(1942);

    Http::assertSentCount(2);
});

test('failures are not cached', function (): void {
    fakeIgdbExternalGames(['message' => 'down'], 503);
    $provider = new IgdbGameDataProvider;

    expect($provider->findGameIdByExternalUid(GameLauncher::Gog, '1'))->toBeNull()
        ->and($provider->findGameIdByExternalUid(GameLauncher::Gog, '1'))->toBeNull();

    Http::assertSentCount(3);
});

test('nothing is resolved without IGDB credentials', function (): void {
    Config::set('services.igdb.client_id', '');
    Http::fake();

    expect(new IgdbGameDataProvider()->findGameIdByExternalUid(GameLauncher::Gog, '1'))->toBeNull();

    Http::assertNothingSent();
});
