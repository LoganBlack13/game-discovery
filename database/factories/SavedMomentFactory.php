<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\SavedMoment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SavedMoment>
 */
final class SavedMomentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = (string) Str::uuid();

        return [
            'id' => $id,
            'game_session_id' => GameSession::factory(),
            'user_id' => fn (array $attributes): mixed => GameSession::query()->whereKey($attributes['game_session_id'])->value('user_id'),
            'game_id' => fn (array $attributes): mixed => GameSession::query()->whereKey($attributes['game_session_id'])->value('game_id'),
            'companion_device_id' => fn (array $attributes): mixed => GameSession::query()->whereKey($attributes['game_session_id'])->value('companion_device_id'),
            'captured_at' => now()->subMinutes(5),
            'disk' => 'local',
            'path' => fn (array $attributes): string => sprintf('moments/%s/%s.jpg', is_string($attributes['user_id']) ? $attributes['user_id'] : '', $id),
            'thumbnail_path' => null,
            'width' => 1920,
            'height' => 1080,
            'size_bytes' => 350_000,
            'caption' => null,
        ];
    }
}
