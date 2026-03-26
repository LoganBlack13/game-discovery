<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\EnrichmentRun>
 */
final class EnrichmentRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-7 days', 'now');
        $finishedAt = (clone $startedAt)->modify('+'.rand(10, 120).' seconds');

        return [
            'run_id' => Str::uuid()->toString(),
            'status' => $this->faker->randomElement(['completed', 'failed', 'running']),
            'feeds_total' => $total = rand(3, 10),
            'feeds_done' => rand(0, $total),
            'created_count' => rand(0, 50),
            'error' => null,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ];
    }
}
