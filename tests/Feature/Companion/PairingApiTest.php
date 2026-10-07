<?php

declare(strict_types=1);

use App\Models\CompanionDevice;
use App\Models\CompanionPairing;
use App\Models\User;
use Database\Factories\CompanionPairingFactory;
use Laravel\Sanctum\PersonalAccessToken;

function issueCompanionToken(CompanionDevice $device): string
{
    $user = $device->user;
    assert($user instanceof User);
    $token = $user->createToken("companion:{$device->id}", [CompanionDevice::TOKEN_ABILITY]);
    $device->forceFill(['personal_access_token_id' => $token->accessToken->getKey()])->save();

    return $token->plainTextToken;
}

test('a companion can start a pairing', function (): void {
    $response = $this->postJson(route('api.v1.companion.pairings.store'), [
        'label' => 'PC-SALON — Windows',
        'platform' => 'Windows',
        'app_version' => '0.1.0',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.interval', 5)
        ->assertJsonPath('data.expires_at', now()->addMinutes(10)->toIso8601ZuluString());

    $code = $response->json('data.user_code');
    expect($code)->toMatch('/^[ABCDEFGHJKLMNPQRSTUVWXYZ2-9]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ2-9]{4}$/')
        ->and($response->json('data.verification_url'))->toBe(route('companion.link', ['code' => $code]));

    $pairing = CompanionPairing::query()->findOrFail($response->json('data.pairing_id'));
    expect($pairing->user_code)->toBe(str_replace('-', '', $code))
        ->and($pairing->secret_hash)->not->toBe($response->json('data.pairing_secret'))
        ->and($pairing->secretMatches($response->json('data.pairing_secret')))->toBeTrue()
        ->and($pairing->label)->toBe('PC-SALON — Windows');
});

test('starting a pairing validates the device', function (array $payload, string $field): void {
    $this->postJson(route('api.v1.companion.pairings.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing label' => [['platform' => 'Windows'], 'label'],
    'missing platform' => [['label' => 'PC'], 'platform'],
    'label too long' => [['label' => str_repeat('a', 101), 'platform' => 'Windows'], 'label'],
]);

test('pairing requests are throttled per IP', function (): void {
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('api.v1.companion.pairings.store'), ['label' => 'PC', 'platform' => 'Windows'])->assertCreated();
    }

    $this->postJson(route('api.v1.companion.pairings.store'), ['label' => 'PC', 'platform' => 'Windows'])
        ->assertTooManyRequests();
});

test('polling waits while the user has not confirmed the code', function (): void {
    $pairing = CompanionPairing::factory()->create();

    $this->postJson(route('api.v1.companion.pairings.token', $pairing), ['pairing_secret' => CompanionPairingFactory::SECRET])
        ->assertStatus(202);

    expect(CompanionDevice::query()->count())->toBe(0);
});

test('polling with a wrong secret is forbidden', function (): void {
    $pairing = CompanionPairing::factory()->approvedBy(User::factory()->create())->create();

    $this->postJson(route('api.v1.companion.pairings.token', $pairing), ['pairing_secret' => 'nope'])
        ->assertForbidden();

    expect($pairing->fresh()?->consumed_at)->toBeNull();
});

test('polling an unknown pairing is not found', function (): void {
    $this->postJson(route('api.v1.companion.pairings.token', ['pairing' => '0199a1b2-0000-7000-8000-000000000000']), ['pairing_secret' => 'x'])
        ->assertNotFound();
});

test('an approved pairing delivers its token exactly once', function (): void {
    $user = User::factory()->create();
    $pairing = CompanionPairing::factory()->approvedBy($user)->create(['label' => 'Bureau', 'app_version' => '0.2.0']);
    $url = route('api.v1.companion.pairings.token', $pairing);

    $response = $this->postJson($url, ['pairing_secret' => CompanionPairingFactory::SECRET]);

    $response->assertSuccessful()->assertJsonStructure(['data' => ['token', 'device_id']]);
    $device = CompanionDevice::query()->findOrFail($response->json('data.device_id'));
    expect($device->user_id)->toBe($user->id)
        ->and($device->label)->toBe('Bureau')
        ->and($device->app_version)->toBe('0.2.0')
        ->and($device->accessToken?->can(CompanionDevice::TOKEN_ABILITY))->toBeTrue()
        ->and($pairing->fresh()?->companion_device_id)->toBe($device->id);

    $this->postJson($url, ['pairing_secret' => CompanionPairingFactory::SECRET])->assertGone();
    expect(CompanionDevice::query()->count())->toBe(1);
});

test('an expired pairing is gone', function (): void {
    $pairing = CompanionPairing::factory()->approvedBy(User::factory()->create())->expired()->create();

    $this->postJson(route('api.v1.companion.pairings.token', $pairing), ['pairing_secret' => CompanionPairingFactory::SECRET])
        ->assertGone();
});

test('the delivered token identifies the user and the device', function (): void {
    $user = User::factory()->create(['username' => 'logan']);
    $pairing = CompanionPairing::factory()->approvedBy($user)->create(['label' => 'Bureau']);
    $token = $this->postJson(route('api.v1.companion.pairings.token', $pairing), ['pairing_secret' => CompanionPairingFactory::SECRET])
        ->json('data.token');

    $this->withToken($token)->getJson(route('api.v1.companion.me'))
        ->assertSuccessful()
        ->assertJsonPath('data.user.username', 'logan')
        ->assertJsonPath('data.device.label', 'Bureau')
        ->assertJsonPath('data.device.platform', 'Windows');
});

test('me requires a token', function (): void {
    $this->getJson(route('api.v1.companion.me'))->assertUnauthorized();
});

test('a revoked device is rejected', function (): void {
    $device = CompanionDevice::factory()->create();
    $token = issueCompanionToken($device);
    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertSuccessful();

    $device->revoke();
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertUnauthorized();
    expect(PersonalAccessToken::query()->count())->toBe(0)
        ->and($device->fresh()?->revoked_at)->not->toBeNull();
});

test('a token without the companion ability is rejected', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('other', ['other'])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertUnauthorized();
});

test('a companion token whose device record is missing is rejected', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('companion:orphan', [CompanionDevice::TOKEN_ABILITY])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertUnauthorized();
});

test('api activity updates last seen at most once per minute', function (): void {
    $device = CompanionDevice::factory()->create(['last_seen_at' => now()->subHour()]);
    $token = issueCompanionToken($device);

    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertSuccessful();
    expect($device->fresh()?->last_seen_at?->toDateTimeString())->toBe(now()->toDateTimeString());

    $this->travel(30)->seconds();
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson(route('api.v1.companion.me'))->assertSuccessful();
    expect($device->fresh()?->last_seen_at?->toDateTimeString())->toBe(now()->subSeconds(30)->toDateTimeString());
});

test('a companion token does not open web pages', function (): void {
    $device = CompanionDevice::factory()->create();
    $token = issueCompanionToken($device);

    $this->withToken($token)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->withToken($token)->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
