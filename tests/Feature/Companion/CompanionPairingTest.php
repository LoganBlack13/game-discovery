<?php

declare(strict_types=1);

use App\Enums\CompanionPairingState;
use App\Models\CompanionPairing;
use App\Models\User;
use App\Services\CompanionPairingService;

test('codes are normalized and formatted', function (string $typed, string $normalized, string $formatted): void {
    expect(CompanionPairing::normalizeCode($typed))->toBe($normalized)
        ->and(CompanionPairing::formatCode($typed))->toBe($formatted);
})->with([
    'display form' => ['KQ7M-4XPD', 'KQ7M4XPD', 'KQ7M-4XPD'],
    'lower case with spaces' => [' kq7m 4xpd ', 'KQ7M4XPD', 'KQ7M-4XPD'],
    'incomplete' => ['kq7', 'KQ7', 'KQ7'],
]);

test('a pairing goes through its states', function (): void {
    $user = User::factory()->create();

    expect(CompanionPairing::factory()->create()->state())->toBe(CompanionPairingState::Pending)
        ->and(CompanionPairing::factory()->approvedBy($user)->create()->state())->toBe(CompanionPairingState::Approved)
        ->and(CompanionPairing::factory()->approvedBy($user)->consumed()->create()->state())->toBe(CompanionPairingState::Consumed)
        ->and(CompanionPairing::factory()->approvedBy($user)->expired()->create()->state())->toBe(CompanionPairingState::Expired);
});

test('generated codes avoid ambiguous characters', function (): void {
    $service = app(CompanionPairingService::class);

    foreach (range(1, 30) as $attempt) {
        $code = $service->start('PC', 'Windows', null)['pairing']->user_code;
        expect($code)->toHaveLength(8)->not->toMatch('/[01OI]/');
    }

    expect(CompanionPairing::query()->distinct()->count('user_code'))->toBe(30);
});

test('an approved pairing cannot be approved again', function (): void {
    $service = app(CompanionPairingService::class);
    $pairing = CompanionPairing::factory()->create();

    expect($service->approve($pairing, User::factory()->create(), 'PC'))->toBeTrue()
        ->and($service->approve($pairing, User::factory()->create(), 'PC'))->toBeFalse();
});

test('a pending pairing cannot be claimed', function (): void {
    expect(app(CompanionPairingService::class)->claim(CompanionPairing::factory()->create()))->toBeNull();
});

test('expired pairings older than a day are purged', function (): void {
    $old = CompanionPairing::factory()->create(['expires_at' => now()->subDays(2)]);
    $recent = CompanionPairing::factory()->expired()->create();
    $pending = CompanionPairing::factory()->create();

    $this->artisan('companion:purge-pairings')
        ->expectsOutputToContain('Deleted 1 expired pairing(s).')
        ->assertSuccessful();

    expect(CompanionPairing::query()->pluck('id')->all())->toEqualCanonicalizing([$recent->id, $pending->id])
        ->and($old->fresh())->toBeNull();
});
