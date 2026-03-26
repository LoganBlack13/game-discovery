<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrackedGameStatus;
use Database\Factories\TrackedGameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property string $user_id
 * @property int $game_id
 * @property TrackedGameStatus|null $status
 */
final class TrackedGame extends Model
{
    /** @use HasFactory<TrackedGameFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'user_id',
        'game_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => TrackedGameStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
