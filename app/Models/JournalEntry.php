<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $tracked_game_id
 * @property string $body
 * @property bool $is_resume_goal
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface $written_at
 */
final class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'tracked_game_id',
        'body',
        'is_resume_goal',
        'completed_at',
        'written_at',
    ];

    /**
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'is_resume_goal' => false,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'is_resume_goal' => 'boolean',
            'completed_at' => 'datetime',
            'written_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrackedGame, $this>
     */
    public function trackedGame(): BelongsTo
    {
        return $this->belongsTo(TrackedGame::class);
    }

    public function isOpenResumeGoal(): bool
    {
        return $this->is_resume_goal && $this->completed_at === null;
    }
}
