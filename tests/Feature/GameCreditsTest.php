<?php

declare(strict_types=1);

use App\Enums\CreditDiscipline;
use App\Jobs\SyncGameCreditsJob;
use App\Models\Game;
use App\Models\GameCredit;
use App\Models\Person;
use App\Services\RawgGameDataProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('game page groups credits by discipline and links to people', function (): void {
    $game = Game::factory()->create(['title' => 'Star Voyage']);
    $person = Person::factory()->create(['name' => 'Ada Composer', 'slug' => 'ada-composer']);
    GameCredit::factory()->for($game)->for($person)->create(['role' => 'Composer', 'discipline' => CreditDiscipline::Audio]);
    GameCredit::factory()->for($game)->create(['role' => 'Director', 'discipline' => CreditDiscipline::Direction]);

    $this->get(route('games.show', $game))
        ->assertOk()
        ->assertSee('Credits', false)
        ->assertSeeInOrder(['Direction', 'Music &amp; audio', 'Ada Composer'], false)
        ->assertSee(e(route('people.show', ['person' => $person, 'game' => 'star-voyage'])), false);
});

test('a person with several roles on one game is listed once with every role', function (): void {
    $game = Game::factory()->create();
    $person = Person::factory()->create(['name' => 'Multi Talent']);
    GameCredit::factory()->for($game)->for($person)->create(['role' => 'Lead Programmer', 'discipline' => CreditDiscipline::Programming]);
    GameCredit::factory()->for($game)->for($person)->create(['role' => 'Engine Programmer', 'discipline' => CreditDiscipline::Programming]);

    $response = $this->get(route('games.show', $game))->assertOk();

    expect(mb_substr_count($response->getContent(), '>Multi Talent<'))->toBe(1);
    $response->assertSee('Engine Programmer, Lead Programmer', false);
});

test('missing credits are flagged instead of being presented as empty truth', function (): void {
    $neverImported = Game::factory()->create(['credits_synced_at' => null]);
    $importedButEmpty = Game::factory()->create(['credits_synced_at' => now()]);

    $this->get(route('games.show', $neverImported))
        ->assertOk()
        ->assertSee("Credits haven't been imported for this game yet.", false);

    $this->get(route('games.show', $importedButEmpty))
        ->assertOk()
        ->assertSee("doesn't list individual contributors", false)
        ->assertSee("That doesn't mean nobody is credited", false);
});

test('person page lists every known game with the exact roles', function (): void {
    $person = Person::factory()->create(['name' => 'Jo Writer']);
    $first = Game::factory()->create(['title' => 'First Saga', 'release_date' => now()->subYears(3)]);
    $second = Game::factory()->create(['title' => 'Second Saga', 'release_date' => now()->subYear()]);
    GameCredit::factory()->for($first)->for($person)->create(['role' => 'Writer']);
    GameCredit::factory()->for($second)->for($person)->create(['role' => 'Lead Writer']);
    GameCredit::factory()->for($second)->for($person)->create(['role' => 'Creative Director']);

    $this->get(route('people.show', $person))
        ->assertOk()
        ->assertSee('Jo Writer', false)
        ->assertSee('2 games known on Questlog', false)
        ->assertSeeInOrder(['Second Saga', 'Creative Director', 'Lead Writer', 'First Saga', 'Writer'], false)
        ->assertSee(route('games.show', $first), false)
        ->assertSee('may be incomplete', false);
});

test('person page highlights the contribution on the game the visitor came from', function (): void {
    $person = Person::factory()->create();
    $game = Game::factory()->create(['title' => 'Origin Game', 'slug' => 'origin-game']);
    GameCredit::factory()->for($game)->for($person)->create(['role' => 'Art Director']);

    $this->get(route('people.show', ['person' => $person, 'game' => 'origin-game']))
        ->assertOk()
        ->assertSee('data-from-game', false)
        ->assertSeeInOrder(['Origin Game', 'Art Director'], false);

    $this->get(route('people.show', ['person' => $person, 'game' => 'unrelated']))
        ->assertOk()
        ->assertDontSee('data-from-game', false);
});

test('person page returns 404 for an unknown slug', function (): void {
    $this->get(route('people.show', ['person' => 'nobody']))->assertNotFound();
});

test('credit roles are mapped to disciplines', function (string $role, CreditDiscipline $discipline): void {
    expect(CreditDiscipline::fromRole($role))->toBe($discipline);
})->with([
    ['Director', CreditDiscipline::Direction],
    ['Producer', CreditDiscipline::Production],
    ['Game Designer', CreditDiscipline::Design],
    ['Writer', CreditDiscipline::Writing],
    ['Lead Programmer', CreditDiscipline::Programming],
    ['Concept Artist', CreditDiscipline::Art],
    ['Animator', CreditDiscipline::Animation],
    ['Composer', CreditDiscipline::Audio],
    ['Sound Designer', CreditDiscipline::Audio],
    ['Voice Actor', CreditDiscipline::VoiceActing],
    ['QA Tester', CreditDiscipline::Other],
]);

test('sync job imports the RAWG development team with every role', function (): void {
    Config::set('services.rawg.key', 'test-key');
    Http::fake([
        'api.rawg.io/api/games/42/development-team*' => Http::sequence()
            ->push([
                'next' => 'https://api.rawg.io/api/games/42/development-team?page=2&key=test-key',
                'results' => [
                    ['id' => 7, 'name' => 'Hide Creator', 'slug' => 'hide-creator', 'image' => 'https://img/7.jpg', 'positions' => [
                        ['name' => 'director'], ['name' => 'writer'],
                    ]],
                ],
            ])
            ->push([
                'next' => null,
                'results' => [
                    ['id' => 8, 'name' => 'Sam Sound', 'slug' => 'sam-sound', 'positions' => [['name' => 'composer']]],
                    ['id' => 9, 'name' => '', 'slug' => 'ghost', 'positions' => []],
                ],
            ]),
    ]);
    $game = Game::factory()->create(['external_source' => 'rawg', 'external_id' => '42']);
    GameCredit::factory()->for($game)->create(['role' => 'Stale Role']);

    (new SyncGameCreditsJob($game->id))->handle(resolve(RawgGameDataProvider::class));

    $credits = $game->credits()->with('person')->orderBy('id')->get();
    expect($credits->map(fn (GameCredit $credit): string => $credit->person->name.':'.$credit->role)->all())
        ->toBe(['Hide Creator:Director', 'Hide Creator:Writer', 'Sam Sound:Composer'])
        ->and($credits->first()->discipline)->toBe(CreditDiscipline::Direction)
        ->and($credits->last()->discipline)->toBe(CreditDiscipline::Audio)
        ->and($game->fresh()->credits_synced_at)->not->toBeNull()
        ->and(Person::query()->where('external_id', '7')->first()->image)->toBe('https://img/7.jpg');
});

test('homonyms from the source get distinct people and slugs', function (): void {
    Config::set('services.rawg.key', 'test-key');
    Http::fake([
        'api.rawg.io/api/games/1/development-team*' => Http::response(['next' => null, 'results' => [
            ['id' => 100, 'name' => 'Alex Smith', 'slug' => 'alex-smith', 'positions' => [['name' => 'programmer']]],
        ]]),
        'api.rawg.io/api/games/2/development-team*' => Http::response(['next' => null, 'results' => [
            ['id' => 200, 'name' => 'Alex Smith', 'slug' => 'alex-smith', 'positions' => [['name' => 'artist']]],
            ['id' => 100, 'name' => 'Alex Smith', 'slug' => 'alex-smith', 'positions' => [['name' => 'producer']]],
        ]]),
    ]);
    $first = Game::factory()->create(['external_source' => 'rawg', 'external_id' => '1']);
    $second = Game::factory()->create(['external_source' => 'rawg', 'external_id' => '2']);

    (new SyncGameCreditsJob($first->id))->handle(resolve(RawgGameDataProvider::class));
    (new SyncGameCreditsJob($second->id))->handle(resolve(RawgGameDataProvider::class));

    expect(Person::query()->orderBy('id')->pluck('slug')->all())->toBe(['alex-smith', 'alex-smith-200'])
        ->and(Person::query()->where('external_id', '100')->first()->credits()->count())->toBe(2);
});

test('games from sources without people credits are marked as synced without credits', function (): void {
    $game = Game::factory()->create(['external_source' => 'igdb', 'external_id' => '5']);

    (new SyncGameCreditsJob($game->id))->handle(resolve(RawgGameDataProvider::class));

    expect($game->fresh()->credits_synced_at)->not->toBeNull()
        ->and($game->credits()->count())->toBe(0);
});

test('sync job does nothing for a deleted game', function (): void {
    (new SyncGameCreditsJob(999))->handle(resolve(RawgGameDataProvider::class));

    expect(GameCredit::query()->count())->toBe(0);
});

test('sync job fails without touching credits when RAWG is not configured', function (): void {
    Config::set('services.rawg.key', null);
    $game = Game::factory()->create(['external_source' => 'rawg', 'external_id' => '42']);
    GameCredit::factory()->for($game)->create();

    $job = (new SyncGameCreditsJob($game->id))->withFakeQueueInteractions();
    $job->handle(resolve(RawgGameDataProvider::class));

    $job->assertFailed();
    expect($game->credits()->count())->toBe(1)
        ->and($game->fresh()->credits_synced_at)->toBeNull();
});

test('sync command dispatches jobs for unsynced and stale games only', function (): void {
    Queue::fake();
    $unsynced = Game::factory()->create(['credits_synced_at' => null]);
    $stale = Game::factory()->create(['credits_synced_at' => now()->subDays(40)]);
    Game::factory()->create(['credits_synced_at' => now()->subDay()]);

    $this->artisan('games:sync-credits')->assertSuccessful();

    Queue::assertPushed(SyncGameCreditsJob::class, 2);
    Queue::assertPushed(SyncGameCreditsJob::class, fn (SyncGameCreditsJob $job): bool => $job->gameId === $unsynced->id);
    Queue::assertPushed(SyncGameCreditsJob::class, fn (SyncGameCreditsJob $job): bool => $job->gameId === $stale->id);
});

test('sync command can target a single game', function (): void {
    Queue::fake();
    $game = Game::factory()->create(['title' => 'Only Me', 'credits_synced_at' => now()]);
    Game::factory()->create(['credits_synced_at' => null]);

    $this->artisan('games:sync-credits', ['--game' => 'only-me'])->assertSuccessful();

    Queue::assertPushed(SyncGameCreditsJob::class, 1);
    Queue::assertPushed(SyncGameCreditsJob::class, fn (SyncGameCreditsJob $job): bool => $job->gameId === $game->id);
});

test('sync job retries with a backoff', function (): void {
    expect((new SyncGameCreditsJob(1))->backoff())->toBe([60, 300])
        ->and((new SyncGameCreditsJob(1))->tries)->toBe(3);
});
