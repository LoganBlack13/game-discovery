<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreditDiscipline;
use App\Models\Game;
use App\Models\GameCredit;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameCredit>
 */
final class GameCreditFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $role */
        $role = fake()->randomElement(['Director', 'Lead Programmer', 'Composer', 'Writer', 'Game Designer']);

        return [
            'game_id' => Game::factory(),
            'person_id' => Person::factory(),
            'role' => $role,
            'discipline' => CreditDiscipline::fromRole($role),
        ];
    }
}
