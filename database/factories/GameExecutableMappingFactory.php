<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MappingConfidence;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameExecutableMapping>
 */
final class GameExecutableMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => null,
            'executable_name' => fake()->unique()->lexify('????????').'.exe',
            'confidence' => MappingConfidence::High,
        ];
    }
}
