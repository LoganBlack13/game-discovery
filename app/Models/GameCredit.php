<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreditDiscipline;
use Database\Factories\GameCreditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $game_id
 * @property int $person_id
 * @property string $role
 * @property CreditDiscipline $discipline
 * @property-read Game $game
 * @property-read Person $person
 */
final class GameCredit extends Model
{
    /** @use HasFactory<GameCreditFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'game_id',
        'person_id',
        'role',
        'discipline',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'discipline' => CreditDiscipline::class,
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
