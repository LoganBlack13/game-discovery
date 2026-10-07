<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompanionPairingState;
use App\Models\CompanionDevice;
use App\Models\CompanionPairing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Device authorization flow for Questlog Companion: the desktop app asks for a pairing, the user confirms
 * the short code on the web, then the app claims a token bound to a new device (fiche A1).
 */
final class CompanionPairingService
{
    /** Characters of a user code: no 0/O or 1/I to avoid typing mistakes. */
    public const string CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const int CODE_LENGTH = 8;

    public const int TTL_MINUTES = 10;

    public const int POLL_INTERVAL_SECONDS = 5;

    /**
     * @return array{pairing: CompanionPairing, secret: string}
     */
    public function start(string $label, string $platform, ?string $appVersion): array
    {
        $secret = Str::random(40);

        $pairing = CompanionPairing::query()->create([
            'user_code' => $this->uniqueCode(),
            'secret_hash' => hash('sha256', $secret),
            'label' => $label,
            'platform' => $platform,
            'app_version' => $appVersion,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return ['pairing' => $pairing, 'secret' => $secret];
    }

    /**
     * Finds a pairing that still waits for confirmation, whatever the case or separators typed by the user.
     */
    public function findPendingByCode(string $code): ?CompanionPairing
    {
        $pairing = CompanionPairing::query()
            ->where('user_code', CompanionPairing::normalizeCode($code))
            ->first();

        return $pairing?->state() === CompanionPairingState::Pending ? $pairing : null;
    }

    /**
     * Links the pairing to the user. Returns false if it was confirmed, used or expired meanwhile.
     */
    public function approve(CompanionPairing $pairing, User $user, string $label): bool
    {
        return DB::transaction(function () use ($pairing, $user, $label): bool {
            $locked = CompanionPairing::query()->lockForUpdate()->find($pairing->id);

            if ($locked?->state() !== CompanionPairingState::Pending) {
                return false;
            }

            $locked->forceFill([
                'user_id' => $user->id,
                'label' => mb_trim($label),
                'approved_at' => now(),
            ])->save();

            return true;
        });
    }

    /**
     * Creates the device and its token for an approved pairing. The plain-text token is returned only once:
     * null means the pairing is not (or no longer) claimable.
     *
     * @return array{token: string, device: CompanionDevice}|null
     */
    public function claim(CompanionPairing $pairing): ?array
    {
        return DB::transaction(function () use ($pairing): ?array {
            $locked = CompanionPairing::query()->lockForUpdate()->find($pairing->id);

            if ($locked?->state() !== CompanionPairingState::Approved || $locked->user_id === null) {
                return null;
            }

            $user = User::query()->findOrFail($locked->user_id);

            $device = CompanionDevice::query()->create([
                'user_id' => $user->id,
                'label' => $locked->label,
                'platform' => $locked->platform,
                'app_version' => $locked->app_version,
                'last_seen_at' => now(),
            ]);

            $token = $user->createToken("companion:{$device->id}", [CompanionDevice::TOKEN_ABILITY]);

            $device->forceFill(['personal_access_token_id' => $token->accessToken->getKey()])->save();
            $locked->forceFill([
                'consumed_at' => now(),
                'companion_device_id' => $device->id,
            ])->save();

            return ['token' => $token->plainTextToken, 'device' => $device];
        });
    }

    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($index = 0; $index < self::CODE_LENGTH; $index++) {
                $code .= self::CODE_ALPHABET[random_int(0, mb_strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (CompanionPairing::query()->where('user_code', $code)->exists());

        return $code;
    }
}
