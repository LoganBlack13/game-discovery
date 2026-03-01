<?php

declare(strict_types=1);

use App\Services\IgdbGameDataProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.igdb.client_id', '');
    Config::set('services.igdb.client_secret', '');
});

test('search returns empty array when IGDB credentials are not configured', function (): void {
    $provider = new IgdbGameDataProvider;

    $results = $provider->search('zelda');

    expect($results)->toBeArray()->toBeEmpty();
});

test('getGameDetails throws when IGDB credentials are not configured', function (): void {
    $provider = new IgdbGameDataProvider;

    $provider->getGameDetails('12345');
})->throws(InvalidArgumentException::class, 'IGDB (Twitch) credentials are not configured.');

test('search returns mapped results with external_source igdb when HTTP is faked', function (): void {
    Config::set('services.igdb.client_id', 'test-client-id');
    Config::set('services.igdb.client_secret', 'test-client-secret');

    Http::fake([
        'https://id.twitch.tv/*' => Http::response([
            'access_token' => 'fake-token',
            'expires_in' => 3600,
        ]),
        'https://api.igdb.com/*' => Http::response([
            [
                'id' => 1905,
                'name' => 'The Legend of Zelda',
                'slug' => 'the-legend-of-zelda',
                'summary' => 'A great game.',
                'first_release_date' => 536371200,
                'genres' => [['name' => 'Adventure']],
                'involved_companies' => [
                    ['company' => ['name' => 'Nintendo'], 'developer' => true, 'publisher' => true],
                ],
                'cover' => ['image_id' => 'co2lv2oi'],
                'platforms' => [['name' => 'Nintendo Entertainment System']],
            ],
        ]),
    ]);

    $provider = new IgdbGameDataProvider;
    $results = $provider->search('zelda');

    expect($results)->toHaveCount(1);
    expect($results[0])->toHaveKeys([
        'title', 'slug', 'description', 'cover_image', 'developer', 'publisher',
        'genres', 'platforms', 'release_date', 'release_status', 'external_id', 'external_source',
    ]);
    expect($results[0]['external_source'])->toBe('igdb');
    expect($results[0]['external_id'])->toBe('1905');
    expect($results[0]['title'])->toBe('The Legend of Zelda');
    expect($results[0]['developer'])->toBe('Nintendo');
    expect($results[0]['publisher'])->toBe('Nintendo');
});

test('getGameDetails returns contract shape with external_source igdb when HTTP is faked', function (): void {
    Config::set('services.igdb.client_id', 'test-client-id');
    Config::set('services.igdb.client_secret', 'test-client-secret');

    Http::fake([
        'https://id.twitch.tv/*' => Http::response([
            'access_token' => 'fake-token',
            'expires_in' => 3600,
        ]),
        'https://api.igdb.com/*' => Http::response([
            [
                'id' => 1905,
                'name' => 'The Legend of Zelda',
                'slug' => 'the-legend-of-zelda',
                'summary' => 'A great game.',
                'first_release_date' => 536371200,
                'genres' => [['name' => 'Adventure']],
                'involved_companies' => [
                    ['company' => ['name' => 'Nintendo'], 'developer' => true, 'publisher' => false],
                    ['company' => ['name' => 'Nintendo of America'], 'developer' => false, 'publisher' => true],
                ],
                'cover' => ['image_id' => 'co2lv2oi'],
                'platforms' => [['name' => 'NES']],
            ],
        ]),
    ]);

    $provider = new IgdbGameDataProvider;
    $details = $provider->getGameDetails('1905');

    expect($details['external_source'])->toBe('igdb');
    expect($details['external_id'])->toBe('1905');
    expect($details['title'])->toBe('The Legend of Zelda');
    expect($details['slug'])->toBe('the-legend-of-zelda');
    expect($details['description'])->toBe('A great game.');
    expect($details['developer'])->toBe('Nintendo');
    expect($details['publisher'])->toBe('Nintendo of America');
    expect($details['cover_image'])->toContain('co2lv2oi');
    expect($details['genres'])->toBe(['Adventure']);
    expect($details['platforms'])->toBe(['NES']);
    expect($details['release_date'])->not->toBeNull();
    expect($details['release_status'])->toBeIn(['released', 'announced', 'coming_soon', 'delayed']);
});
