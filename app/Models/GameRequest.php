<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\GameRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int $id
 * @property string $normalized_title
 * @property string $display_title
 * @property int $request_count
 * @property string $status
 * @property int|null $game_id
 * @property CarbonInterface|null $added_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class GameRequest extends Model
{
    /** @use HasFactory<GameRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'normalized_title',
        'display_title',
        'request_count',
        'status',
        'game_id',
        'added_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'added_at' => 'datetime',
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
     * @return HasMany<GameRequestVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(GameRequestVote::class);
    }

    #[Scope]
    public function pending(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereNull('game_id');
    }
}
