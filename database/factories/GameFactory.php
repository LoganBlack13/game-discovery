<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReleaseStatus;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
final class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->optional(0.7)->paragraph(),
            'cover_image' => fake()->optional(0.5)->imageUrl(400, 600, 'games'),
            'developer' => fake()->optional(0.8)->company(),
            'publisher' => fake()->optional(0.6)->company(),
            'genres' => fake()->randomElements(['RPG', 'Action', 'Adventure', 'Strategy', 'Racing', 'Shooter', 'Puzzle'], fake()->numberBetween(1, 3)),
            'platforms' => fake()->randomElements(['PC', 'PlayStation', 'Xbox', 'Switch'], fake()->numberBetween(1, 3)),
            'release_date' => fake()->optional(0.8)->dateTimeBetween('-1 year', '+1 year'),
            'release_status' => fake()->randomElement(ReleaseStatus::cases()),
        ];
    }
}
