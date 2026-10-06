<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use App\Models\TrackedGame;
use App\Models\TrackedGameStatusChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedGameStatusChange>
 */
final class TrackedGameStatusChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracked_game_id' => TrackedGame::factory(),
            'from_status' => TrackedGameStatus::ToPlay,
            'to_status' => TrackedGameStatus::Playing,
            'occurred_at' => now(),
        ];
    }

    public function paused(?InterruptionReason $reason = null, ?string $comment = null): self
    {
        return $this->state(fn (): array => [
            'from_status' => TrackedGameStatus::Playing,
            'to_status' => TrackedGameStatus::Paused,
            'reason' => $reason,
            'comment' => $comment,
        ]);
    }
}
