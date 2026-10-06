<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use Carbon\CarbonInterface;
use Database\Factories\TrackedGameStatusChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $tracked_game_id
 * @property TrackedGameStatus|null $from_status
 * @property TrackedGameStatus|null $to_status
 * @property InterruptionReason|null $reason
 * @property string|null $comment
 * @property CarbonInterface $occurred_at
 */
final class TrackedGameStatusChange extends Model
{
    /** @use HasFactory<TrackedGameStatusChangeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'tracked_game_id',
        'from_status',
        'to_status',
        'reason',
        'comment',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'from_status' => TrackedGameStatus::class,
            'to_status' => TrackedGameStatus::class,
            'reason' => InterruptionReason::class,
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrackedGame, $this>
     */
    public function trackedGame(): BelongsTo
    {
        return $this->belongsTo(TrackedGame::class);
    }

    public function isInterruption(): bool
    {
        return $this->to_status?->isInterruption() ?? false;
    }
}
