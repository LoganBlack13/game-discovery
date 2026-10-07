<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompanionDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanionDevice>
 */
final class CompanionDeviceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => mb_strtoupper(fake()->unique()->lexify('PC-????')).' — Windows',
            'platform' => 'Windows',
            'app_version' => '0.1.0',
            'last_seen_at' => now(),
        ];
    }

    public function revoked(): self
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
