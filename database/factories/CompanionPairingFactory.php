<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompanionPairing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanionPairing>
 */
final class CompanionPairingFactory extends Factory
{
    public const string SECRET = 'factory-pairing-secret';

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_code' => mb_strtoupper(fake()->unique()->bothify('????####')),
            'secret_hash' => hash('sha256', self::SECRET),
            'label' => 'PC-SALON — Windows',
            'platform' => 'Windows',
            'app_version' => '0.1.0',
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function approvedBy(User $user): self
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }

    public function consumed(): self
    {
        return $this->state(fn (): array => ['consumed_at' => now()]);
    }
}
