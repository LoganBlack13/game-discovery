<?php

declare(strict_types=1);

use App\Models\CompanionDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}

/**
 * Gives a Companion device a real API token and returns its plain-text value.
 */
function companionToken(CompanionDevice $device): string
{
    $user = $device->user;
    assert($user instanceof User);
    $token = $user->createToken("companion:{$device->id}", [CompanionDevice::TOKEN_ABILITY]);
    $device->forceFill(['personal_access_token_id' => $token->accessToken->getKey()])->save();

    return $token->plainTextToken;
}
