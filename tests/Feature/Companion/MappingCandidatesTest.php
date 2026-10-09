<?php

declare(strict_types=1);

use App\Enums\GameLauncher;
use App\Enums\MappingCandidateStatus;
use App\Enums\MappingConfidence;
use App\Models\CompanionDevice;
use App\Models\CompanionMappingCandidate;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Services\CompanionMappingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.igdb.client_id', 'test-client-id');
    Config::set('services.igdb.client_secret', 'test-client-secret');
    $this->device = CompanionDevice::factory()->create();
    $this->token = companionToken($this->device);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function candidate(array $overrides = []): array
{
    return [
        'fingerprint' => 'a1b2c3d4e5f60718',
        'executable_name' => 'OuterWilds.exe',
        'path_fragment' => 'Outer Wilds',
        'launcher' => null,
        'launcher_game_id' => null,
        'display_name' => null,
        'product_name' => 'Outer Wilds',
        'total_seconds' => 1200,
        'first_seen_at' => now()->subHour()->toIso8601ZuluString(),
        'last_seen_at' => now()->subMinutes(5)->toIso8601ZuluString(),
        ...$overrides,
    ];
}

function postCandidates(mixed $test, array $candidates): Illuminate\Testing\TestResponse
{
    return $test->withToken($test->token)->postJson(route('api.v1.companion.mapping-candidates.store'), ['candidates' => $candidates]);
}

test('a launcher identifier known by IGDB maps the game automatically', function (): void {
    $game = Game::factory()->create(['external_source' => 'igdb', 'external_id' => '1942']);
    Http::fake([
        'https://id.twitch.tv/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        'https://api.igdb.com/v4/external_games' => Http::response([['game' => 1942]]),
    ]);

    $response = postCandidates($this, [candidate(['launcher' => 'gog', 'launcher_game_id' => '1207659', 'total_seconds' => 0])])
        ->assertSuccessful()
        ->assertJsonPath('data.0.status', 'mapped')
        ->assertJsonPath('data.0.proposed_game_id', $game->id);

    $mapping = GameExecutableMapping::query()->findOrFail($response->json('data.0.mapping_id'));
    expect($mapping->user_id)->toBe($this->device->user_id)
        ->and($mapping->game_id)->toBe($game->id)
        ->and($mapping->launcher)->toBe(GameLauncher::Gog)
        ->and($mapping->confidence)->toBe(MappingConfidence::High)
        ->and($mapping->validated_by_user)->toBeFalse();

    $this->withToken($this->token)->getJson(route('api.v1.companion.mappings.index'))->assertJsonPath('data.0.id', $mapping->id);
});

test('a game known by IGDB but missing from Questlog stays pending', function (): void {
    Http::fake([
        'https://id.twitch.tv/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
        'https://api.igdb.com/v4/external_games' => Http::response([['game' => 555]]),
    ]);

    postCandidates($this, [candidate(['launcher' => 'epic', 'launcher_game_id' => 'abc'])])
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.mapping_id', null);

    expect(CompanionMappingCandidate::query()->sole()->igdb_game_id)->toBe(555);
});

test('a title match is only proposed', function (): void {
    $game = Game::factory()->create(['title' => 'Outer Wilds']);
    Game::factory()->create(['title' => 'Outer Wilds: Echoes of the Eye']);

    postCandidates($this, [candidate(['product_name' => 'OUTER WILDS™'])])
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.proposed_game_id', $game->id)
        ->assertJsonPath('data.0.mapping_id', null);

    expect(CompanionMappingCandidate::query()->sole()->proposal_confidence)->toBe(MappingConfidence::Medium)
        ->and(GameExecutableMapping::query()->count())->toBe(0);
});

test('several games with the same title give no proposal', function (): void {
    Game::factory()->create(['title' => 'Outer Wilds']);
    $duplicate = Game::factory()->create(['title' => 'Outer Wilds Remake']);
    Game::query()->whereKey($duplicate->id)->update(['title' => 'OUTER WILDS']);

    postCandidates($this, [candidate()])->assertJsonPath('data.0.proposed_game_id', null);
});

test('a candidate without a usable name gets no proposal', function (): void {
    Game::factory()->create(['title' => 'Game']);

    postCandidates($this, [candidate(['product_name' => '™', 'display_name' => null])])->assertJsonPath('data.0.proposed_game_id', null);
});

test('sending a candidate again updates it without duplicating it', function (): void {
    postCandidates($this, [candidate(['total_seconds' => 1200])]);
    postCandidates($this, [candidate([
        'total_seconds' => 900,
        'first_seen_at' => now()->subDay()->toIso8601ZuluString(),
        'last_seen_at' => now()->toIso8601ZuluString(),
    ])])->assertSuccessful();

    $stored = CompanionMappingCandidate::query()->sole();
    expect($stored->total_seconds)->toBe(1200)
        ->and($stored->first_seen_at->toDateTimeString())->toBe(now()->subDay()->toDateTimeString())
        ->and($stored->last_seen_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('an existing personal mapping of the same executable is reused', function (): void {
    $mapping = GameExecutableMapping::factory()->create([
        'user_id' => $this->device->user_id,
        'executable_name' => 'outerwilds.exe',
        'path_fragment' => 'Outer Wilds',
    ]);

    postCandidates($this, [candidate()])
        ->assertJsonPath('data.0.status', 'mapped')
        ->assertJsonPath('data.0.mapping_id', $mapping->id);

    expect(GameExecutableMapping::query()->count())->toBe(1);
});

test('ignored candidates are not resolved again', function (): void {
    CompanionMappingCandidate::factory()->create([
        'user_id' => $this->device->user_id,
        'fingerprint' => 'a1b2c3d4e5f60718',
        'status' => MappingCandidateStatus::Ignored,
    ]);
    Game::factory()->create(['title' => 'Outer Wilds']);

    postCandidates($this, [candidate()])->assertJsonPath('data.0.status', 'ignored')->assertJsonPath('data.0.proposed_game_id', null);
});

test('candidates are validated', function (array $overrides, string $field): void {
    postCandidates($this, [candidate($overrides)])->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'missing fingerprint' => [['fingerprint' => null], 'candidates.0.fingerprint'],
    'unknown launcher' => [['launcher' => 'steam', 'launcher_game_id' => '1'], 'candidates.0.launcher'],
    'launcher without id' => [['launcher' => 'gog'], 'candidates.0.launcher_game_id'],
    'negative time' => [['total_seconds' => -1], 'candidates.0.total_seconds'],
]);

test('at most a hundred candidates are accepted at once', function (): void {
    $candidates = array_map(fn (int $index): array => candidate(['fingerprint' => "fp{$index}"]), range(1, 101));

    postCandidates($this, $candidates)->assertUnprocessable()->assertJsonValidationErrors('candidates');
});

test('a sequel numbered with digits proposes the game numbered in roman numerals', function (): void {
    $game = Game::factory()->create(['title' => 'Graveyard Keeper II']);
    Game::factory()->create(['title' => 'Graveyard Keeper']);

    postCandidates($this, [candidate(['product_name' => 'Graveyard Keeper 2'])])
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.proposed_game_id', $game->id);

    expect(GameExecutableMapping::query()->count())->toBe(0);
});

test('candidates require a companion token', function (): void {
    $this->postJson(route('api.v1.companion.mapping-candidates.store'), ['candidates' => [candidate()]])->assertUnauthorized();
});

test('titles are normalized before comparison', function (string $title, string $normalized): void {
    expect(CompanionMappingService::normalizeTitle($title))->toBe($normalized);
})->with([
    ['ELDEN RING™', 'elden ring'],
    ['Hades II', 'hades 2'],
    ['Final Fantasy XIV Online', 'final fantasy 14 online'],
    ['I Am Bread', 'i am bread'],
    ["Baldur's Gate 3", 'baldur s gate 3'],
    ['  ', ''],
]);

test('settings report the suggestion preference and the candidates waiting for the user', function (): void {
    CompanionMappingCandidate::factory()->count(2)->create(['user_id' => $this->device->user_id]);
    CompanionMappingCandidate::factory()->installedOnly()->create(['user_id' => $this->device->user_id]);
    CompanionMappingCandidate::factory()->create(['user_id' => $this->device->user_id, 'status' => MappingCandidateStatus::Ignored]);

    $this->withToken($this->token)->getJson(route('api.v1.companion.settings'))
        ->assertJsonPath('data.suggest_unknown_games', true)
        ->assertJsonPath('data.pending_candidates', 2);
});
