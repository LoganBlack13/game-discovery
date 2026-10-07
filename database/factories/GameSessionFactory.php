<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameSessionSource;
use App\Enums\SessionDetectionSource;
use App\Enums\SessionEndReason;
use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
final class GameSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'companion_device_id' => CompanionDevice::factory(),
            'user_id' => fn (array $attributes): mixed => CompanionDevice::query()->whereKey($attributes['companion_device_id'])->value('user_id'),
            'game_id' => Game::factory(),
            'source' => GameSessionSource::QuestlogCompanion,
            'detection_source' => SessionDetectionSource::Process,
            'started_at' => now()->subHour(),
            'last_heartbeat_at' => now()->subMinute(),
            'active_seconds' => 3480,
            'idle_seconds' => 0,
        ];
    }

    public function ended(SessionEndReason $reason = SessionEndReason::Closed): self
    {
        return $this->state(fn (array $attributes): array => [
            'ended_at' => $attributes['last_heartbeat_at'],
            'end_reason' => $reason,
        ]);
    }
}
