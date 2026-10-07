<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CompanionDeviceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Override;

/**
 * A computer running Questlog Companion, paired with a user account through its own API token.
 *
 * @property int $id
 * @property string $user_id
 * @property string $label
 * @property string $platform
 * @property string|null $app_version
 * @property int|null $personal_access_token_id
 * @property CarbonInterface|null $last_seen_at
 * @property CarbonInterface|null $revoked_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class CompanionDevice extends Model
{
    /** @use HasFactory<CompanionDeviceFactory> */
    use HasFactory;

    public const string TOKEN_ABILITY = 'companion';

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'user_id',
        'label',
        'platform',
        'app_version',
        'personal_access_token_id',
        'last_seen_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PersonalAccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    /**
     * @param  Builder<CompanionDevice>  $query
     * @return Builder<CompanionDevice>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Records API activity at most once per minute to avoid a write on every request.
     */
    public function markSeen(): void
    {
        if ($this->last_seen_at instanceof CarbonInterface && $this->last_seen_at->gt(now()->subMinute())) {
            return;
        }

        $this->forceFill(['last_seen_at' => now()])->save();
    }

    /**
     * Deletes the device token so every later request is rejected. A revoked device is never reactivated.
     */
    public function revoke(): void
    {
        if ($this->isRevoked()) {
            return;
        }

        DB::transaction(function (): void {
            if ($this->personal_access_token_id !== null) {
                PersonalAccessToken::query()->whereKey($this->personal_access_token_id)->delete();
            }

            $this->forceFill(['revoked_at' => now()])->save();
        });
    }
}
