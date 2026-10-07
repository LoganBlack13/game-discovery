<?php

declare(strict_types=1);

use App\Models\CompanionPairing;
use App\Models\User;
use Livewire\Livewire;

test('guests are sent to the login page', function (): void {
    $this->get(route('companion.link', ['code' => 'ABCD-EFGH']))->assertRedirect(route('login'));
});

test('the page shows the code and the proposed device name', function (): void {
    $user = User::factory()->create();
    CompanionPairing::factory()->create(['user_code' => 'KQ7M4XPD', 'label' => 'PC-SALON — Windows']);

    $this->actingAs($user)
        ->get(route('companion.link', ['code' => 'KQ7M-4XPD']))
        ->assertSuccessful()
        ->assertSee('Connect Questlog Companion');

    Livewire::actingAs($user)
        ->withQueryParams(['code' => 'kq7m4xpd'])
        ->test('pages::companion-link')
        ->assertSet('code', 'KQ7M-4XPD')
        ->assertSet('label', 'PC-SALON — Windows');
});

test('confirming a code links the pairing to the user', function (): void {
    $user = User::factory()->create();
    $pairing = CompanionPairing::factory()->create(['user_code' => 'KQ7M4XPD']);

    Livewire::actingAs($user)
        ->withQueryParams(['code' => 'kq7m-4xpd'])
        ->test('pages::companion-link')
        ->set('label', '  Bureau  ')
        ->call('connect')
        ->assertHasNoErrors()
        ->assertSet('connected', true)
        ->assertSee('Device connected');

    $pairing->refresh();
    expect($pairing->user_id)->toBe($user->id)
        ->and($pairing->approved_at)->not->toBeNull()
        ->and($pairing->label)->toBe('Bureau');
});

test('codes that cannot be confirmed are refused', function (Closure $makePairing): void {
    $user = User::factory()->create();
    $pairing = $makePairing();

    Livewire::actingAs($user)
        ->test('pages::companion-link')
        ->set('code', 'KQ7M-4XPD')
        ->set('label', 'PC')
        ->call('connect')
        ->assertHasErrors('code')
        ->assertSet('connected', false);

    if ($pairing instanceof CompanionPairing) {
        expect($pairing->fresh()?->user_id)->not->toBe($user->id);
    }
})->with([
    'unknown' => fn (): Closure => fn (): null => null,
    'expired' => fn (): Closure => fn (): CompanionPairing => CompanionPairing::factory()->expired()->create(['user_code' => 'KQ7M4XPD']),
    'already used' => fn (): Closure => fn (): CompanionPairing => CompanionPairing::factory()->consumed()->create(['user_code' => 'KQ7M4XPD']),
    'already confirmed' => fn (): Closure => fn (): CompanionPairing => CompanionPairing::factory()->approvedBy(User::factory()->create())->create(['user_code' => 'KQ7M4XPD']),
]);

test('the code and the device name are required', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::companion-link')
        ->call('connect')
        ->assertHasErrors(['code' => 'required', 'label' => 'required']);
});

test('wrong codes are throttled for ten minutes after five attempts', function (): void {
    $user = User::factory()->create();
    CompanionPairing::factory()->create(['user_code' => 'GOOD2345']);
    $component = Livewire::actingAs($user)->test('pages::companion-link')->set('label', 'PC');

    foreach (range(1, 5) as $attempt) {
        $component->set('code', 'BAAD-2345')->call('connect')->assertHasErrors('code');
    }

    $component->set('code', 'GOOD-2345')
        ->call('connect')
        ->assertHasErrors('code')
        ->assertSee('Too many wrong codes')
        ->assertSet('connected', false);

    $this->travel(11)->minutes();
    CompanionPairing::factory()->create(['user_code' => 'NEWC2345']);

    $component->set('code', 'NEWC-2345')
        ->call('connect')
        ->assertHasNoErrors()
        ->assertSet('connected', true);
});
