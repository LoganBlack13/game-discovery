<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\TrackedGame;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedGame>
 */
final class TrackedGameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
        ];
    }

    public function status(TrackedGameStatus $status): self
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'last_activity_at' => now(),
        ]);
    }

    public function playing(): self
    {
        return $this->state(fn (): array => [
            'status' => TrackedGameStatus::Playing,
            'started_at' => now()->subWeek(),
            'last_activity_at' => now(),
        ]);
    }

    public function toPlay(): self
    {
        return $this->status(TrackedGameStatus::ToPlay);
    }

    public function completed(): self
    {
        return $this->state(fn (): array => [
            'status' => TrackedGameStatus::Completed,
            'started_at' => now()->subMonth(),
            'last_activity_at' => now(),
            'finished_at' => now(),
        ]);
    }
}
