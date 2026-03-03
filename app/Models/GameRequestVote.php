<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_request_id
 * @property string $user_id
 * @property \Carbon\CarbonInterface $created_at
 * @property \Carbon\CarbonInterface $updated_at
 */
final class GameRequestVote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_request_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<GameRequest, $this>
     */
    public function gameRequest(): BelongsTo
    {
        return $this->belongsTo(GameRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
