<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameRequest;
use App\Services\GameRequestNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRequest>
 */
final class GameRequestFactory extends Factory
{
    protected $model = GameRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayTitle = fake()->words(2, true);

        return [
            'normalized_title' => GameRequestNormalizer::normalize($displayTitle),
            'display_title' => $displayTitle,
            'request_count' => 0,
            'status' => 'pending',
            'game_id' => null,
            'added_at' => null,
        ];
    }

    /**
     * @return static
     */
    public function added(?int $gameId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'added',
            'game_id' => $gameId,
            'added_at' => now(),
        ]);
    }
}
