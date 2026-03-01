<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReleaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property string|null $developer
 * @property string|null $publisher
 * @property array $genres
 * @property array $platforms
 * @property \Carbon\CarbonInterface|null $release_date
 * @property ReleaseStatus $release_status
 * @property string|null $external_id
 * @property string|null $external_source
 * @property \Carbon\CarbonInterface|null $last_synced_at
 */
final class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'developer',
        'publisher',
        'genres',
        'platforms',
        'release_date',
        'release_status',
        'external_id',
        'external_source',
        'last_synced_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'release_date' => 'date',
            'release_status' => ReleaseStatus::class,
            'genres' => 'array',
            'platforms' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('release_date', '>', now())
                ->orWhereIn('release_status', [ReleaseStatus::Announced, ReleaseStatus::ComingSoon]);
        });
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('release_date', '<=', now())
                ->orWhere('release_status', ReleaseStatus::Released);
        });
    }

    public function scopeByReleaseDate(Builder $query): Builder
    {
        return $query->orderBy('release_date');
    }

    /**
     * Order by release date ascending with games that have no release date last.
     */
    public function scopeUpcomingByReleaseDate(Builder $query): Builder
    {
        return $query->orderByRaw('release_date IS NULL')->orderBy('release_date');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<News>
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * @return HasMany<GameActivity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(GameActivity::class)->orderByDesc('occurred_at');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function trackedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tracked_games')->withTimestamps();
    }
}
