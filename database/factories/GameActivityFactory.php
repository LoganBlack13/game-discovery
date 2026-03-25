<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameActivityType;
use App\Models\Game;
use App\Models\GameActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameActivity>
 */
final class GameActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'type' => fake()->randomElement(GameActivityType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->sentence(),
            'url' => null,
            'occurred_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function releaseDateChanged(): static
    {
        return $this->state([
            'type' => GameActivityType::ReleaseDateChanged,
            'title' => 'Release date changed',
            'description' => 'From '.fake()->date('M j, Y').' to '.fake()->date('M j, Y'),
        ]);
    }

    public function gameReleased(): static
    {
        return $this->state([
            'type' => GameActivityType::GameReleased,
            'title' => 'Game released',
            'description' => fake()->date('M j, Y'),
        ]);
    }
}
