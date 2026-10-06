<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BacklogPriority;
use App\Enums\TrackedGameStatus;
use Carbon\CarbonInterface;
use Database\Factories\TrackedGameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @property int $id
 * @property string $user_id
 * @property int $game_id
 * @property TrackedGameStatus|null $status
 * @property string|null $platform
 * @property string|null $progress
 * @property int|null $progress_percent
 * @property string|null $notes
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $last_activity_at
 * @property CarbonInterface|null $finished_at
 * @property BacklogPriority|null $priority
 * @property bool $is_up_next
 * @property int|null $backlog_position
 * @property int|null $rating
 * @property string|null $review
 * @property int|null $playtime_hours
 * @property bool|null $would_recommend
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
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
        'platform',
        'progress',
        'progress_percent',
        'notes',
        'started_at',
        'last_activity_at',
        'finished_at',
        'priority',
        'is_up_next',
        'backlog_position',
        'rating',
        'review',
        'playtime_hours',
        'would_recommend',
    ];

    /**
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'is_up_next' => false,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => TrackedGameStatus::class,
            'priority' => BacklogPriority::class,
            'progress_percent' => 'integer',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'finished_at' => 'datetime',
            'is_up_next' => 'boolean',
            'backlog_position' => 'integer',
            'rating' => 'integer',
            'playtime_hours' => 'integer',
            'would_recommend' => 'boolean',
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

    /**
     * @return HasMany<TrackedGameStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(TrackedGameStatusChange::class)->latest('occurred_at')->latest('id');
    }

    /**
     * @return HasOne<TrackedGameStatusChange, $this>
     */
    public function latestInterruption(): HasOne
    {
        return $this->hasOne(TrackedGameStatusChange::class)
            ->ofMany(['occurred_at' => 'max', 'id' => 'max'], function (Builder $query): void {
                $query->whereIn('to_status', [TrackedGameStatus::Paused->value, TrackedGameStatus::Dropped->value]);
            });
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class)->latest('written_at')->latest('id');
    }

    /**
     * @return HasOne<JournalEntry, $this>
     */
    public function currentResumeGoal(): HasOne
    {
        return $this->hasOne(JournalEntry::class)
            ->ofMany(['written_at' => 'max', 'id' => 'max'], function (Builder $query): void {
                $query->where('is_resume_goal', true)->whereNull('completed_at');
            });
    }

    public function isFinished(): bool
    {
        return $this->status?->isFinished() ?? false;
    }

    public function hasReview(): bool
    {
        return $this->rating !== null
            || $this->would_recommend !== null
            || $this->playtime_hours !== null
            || ($this->review !== null && $this->review !== '');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithStatus(Builder $query, TrackedGameStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Personal backlog order: pinned "play next" first, then manual position, then priority, then oldest tracked.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInBacklogOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_up_next')
            ->orderByRaw('backlog_position IS NULL')
            ->orderBy('backlog_position')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
