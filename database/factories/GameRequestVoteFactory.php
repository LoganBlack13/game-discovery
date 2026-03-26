<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameRequest;
use App\Models\GameRequestVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRequestVote>
 */
final class GameRequestVoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_request_id' => GameRequest::factory(),
            'user_id' => User::factory(),
        ];
    }
}
