<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\TrackedGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
final class JournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracked_game_id' => TrackedGame::factory(),
            'body' => fake()->sentence(),
            'is_resume_goal' => false,
            'written_at' => now(),
        ];
    }

    public function resumeGoal(): self
    {
        return $this->state(fn (): array => ['is_resume_goal' => true]);
    }
}
