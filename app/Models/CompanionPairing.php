<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanionPairingState;
use Carbon\CarbonInterface;
use Database\Factories\CompanionPairingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * A short-lived request from Questlog Companion to be linked to an account, confirmed by the user on the web.
 *
 * @property string $id
 * @property string $user_code
 * @property string $secret_hash
 * @property string $label
 * @property string $platform
 * @property string|null $app_version
 * @property string|null $user_id
 * @property int|null $companion_device_id
 * @property CarbonInterface|null $approved_at
 * @property CarbonInterface|null $consumed_at
 * @property CarbonInterface $expires_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class CompanionPairing extends Model
{
    /** @use HasFactory<CompanionPairingFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'user_code',
        'secret_hash',
        'label',
        'platform',
        'app_version',
        'user_id',
        'companion_device_id',
        'approved_at',
        'consumed_at',
        'expires_at',
    ];

    /**
     * @var list<string>
     */
    #[Override]
    protected $hidden = [
        'secret_hash',
    ];

    /**
     * Uppercases a user-typed code and drops separators: `kq7m-4xpd` becomes `KQ7M4XPD`.
     */
    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Displays a stored code as `KQ7M-4XPD`.
     */
    public static function formatCode(string $code): string
    {
        $normalized = self::normalizeCode($code);

        return mb_strlen($normalized) === 8
            ? mb_substr($normalized, 0, 4).'-'.mb_substr($normalized, 4)
            : $normalized;
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function state(): CompanionPairingState
    {
        return match (true) {
            $this->consumed_at !== null => CompanionPairingState::Consumed,
            $this->expires_at->isPast() => CompanionPairingState::Expired,
            $this->approved_at !== null => CompanionPairingState::Approved,
            default => CompanionPairingState::Pending,
        };
    }

    public function displayCode(): string
    {
        return self::formatCode($this->user_code);
    }

    public function secretMatches(string $secret): bool
    {
        return hash_equals($this->secret_hash, hash('sha256', $secret));
    }
}
