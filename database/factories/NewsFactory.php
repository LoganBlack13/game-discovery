<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
final class NewsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'title' => fake()->sentence(4),
            'thumbnail' => fake()->optional(0.5)->imageUrl(640, 360),
            'source' => fake()->optional(0.7)->domainName(),
            'url' => fake()->url(),
            'published_at' => fake()->optional(0.9)->dateTimeThisYear(),
        ];
    }
}
