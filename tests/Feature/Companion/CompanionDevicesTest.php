<?php

declare(strict_types=1);

use App\Models\CompanionDevice;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

test('the profile shows an empty state without devices', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('Connected devices')
        ->assertSee('No device connected');
});

test('the profile lists only the active devices of the user', function (): void {
    $user = User::factory()->create();
    CompanionDevice::factory()->for($user)->create(['label' => 'Bureau', 'app_version' => '0.1.0']);
    CompanionDevice::factory()->for($user)->revoked()->create(['label' => 'Ancien PC']);
    CompanionDevice::factory()->create(['label' => 'PC du voisin']);

    Livewire::actingAs($user)
        ->test('companion-devices')
        ->assertSee('Bureau')
        ->assertSee('v0.1.0')
        ->assertDontSee('Ancien PC')
        ->assertDontSee('PC du voisin');
});

test('a device can be renamed', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create(['label' => 'Bureau']);

    Livewire::actingAs($user)
        ->test('companion-devices')
        ->call('edit', $device->id)
        ->assertSet('editingLabel', 'Bureau')
        ->set('editingLabel', ' Salon ')
        ->call('saveLabel')
        ->assertHasNoErrors()
        ->assertSet('editingId', null)
        ->assertSee('Salon');

    expect($device->fresh()?->label)->toBe('Salon');
});

test('a device name is required', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('companion-devices')
        ->call('edit', $device->id)
        ->set('editingLabel', '')
        ->call('saveLabel')
        ->assertHasErrors(['editingLabel' => 'required']);
});

test('revoking a device deletes its token', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->create(['label' => 'Bureau']);
    $token = $user->createToken("companion:{$device->id}", [CompanionDevice::TOKEN_ABILITY]);
    $device->forceFill(['personal_access_token_id' => $token->accessToken->getKey()])->save();

    Livewire::actingAs($user)
        ->test('companion-devices')
        ->call('revoke', $device->id)
        ->assertDontSee('Bureau');

    expect($device->fresh()?->isRevoked())->toBeTrue()
        ->and(PersonalAccessToken::query()->count())->toBe(0);

    $this->withToken($token->plainTextToken)->getJson(route('api.v1.companion.me'))->assertUnauthorized();
});

test('the devices of another user cannot be changed', function (string $action): void {
    $device = CompanionDevice::factory()->create(['label' => 'PC du voisin']);

    Livewire::actingAs(User::factory()->create())
        ->test('companion-devices')
        ->call($action, $device->id)
        ->assertForbidden();

    expect($device->fresh()?->isRevoked())->toBeFalse();
})->with(['edit', 'revoke']);

test('a revoked device cannot be renamed', function (): void {
    $user = User::factory()->create();
    $device = CompanionDevice::factory()->for($user)->revoked()->create();

    Livewire::actingAs($user)
        ->test('companion-devices')
        ->call('edit', $device->id)
        ->assertForbidden();
});

test('revoking twice keeps the first revocation date', function (): void {
    $device = CompanionDevice::factory()->revoked()->create(['revoked_at' => now()->subDay()]);

    $device->revoke();

    expect($device->fresh()?->revoked_at?->toDateTimeString())->toBe(now()->subDay()->toDateTimeString());
});
