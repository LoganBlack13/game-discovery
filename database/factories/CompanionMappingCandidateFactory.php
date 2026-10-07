<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompanionMappingCandidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanionMappingCandidate>
 */
final class CompanionMappingCandidateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $executable = fake()->unique()->lexify('????????').'.exe';

        return [
            'user_id' => User::factory(),
            'fingerprint' => hash('sha256', $executable),
            'executable_name' => $executable,
            'path_fragment' => 'Games/'.ucfirst(fake()->word()),
            'display_name' => null,
            'product_name' => null,
            'total_seconds' => 1800,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now()->subHour(),
        ];
    }

    public function installedOnly(): self
    {
        return $this->state(fn (): array => ['total_seconds' => 0]);
    }
}
