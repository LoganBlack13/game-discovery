<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $game_request_id
 * @property string $user_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class GameRequestVote extends Model
{
    use HasFactory;
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
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
