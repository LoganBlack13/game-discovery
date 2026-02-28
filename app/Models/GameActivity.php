<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_id
 * @property GameActivityType $type
 * @property string $title
 * @property string|null $description
 * @property string|null $url
 * @property \Carbon\CarbonInterface $occurred_at
 */
final class GameActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'type',
        'title',
        'description',
        'url',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'type' => GameActivityType::class,
        ];
    }

    /**
     * @return BelongsTo<Game>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
