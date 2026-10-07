<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameLauncher;
use App\Enums\MappingCandidateStatus;
use App\Enums\MappingConfidence;
use Carbon\CarbonInterface;
use Database\Factories\CompanionMappingCandidateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * An executable or installed game the Companion could not associate with a Questlog game (fiche A5).
 *
 * @property int $id
 * @property string $user_id
 * @property int|null $companion_device_id
 * @property string $fingerprint
 * @property string $executable_name
 * @property string|null $path_fragment
 * @property GameLauncher|null $launcher
 * @property string|null $launcher_game_id
 * @property string|null $display_name
 * @property string|null $product_name
 * @property int $total_seconds
 * @property CarbonInterface $first_seen_at
 * @property CarbonInterface $last_seen_at
 * @property MappingCandidateStatus $status
 * @property int|null $proposed_game_id
 * @property MappingConfidence|null $proposal_confidence
 * @property int|null $igdb_game_id
 * @property int|null $game_executable_mapping_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class CompanionMappingCandidate extends Model
{
    /** @use HasFactory<CompanionMappingCandidateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'user_id',
        'companion_device_id',
        'fingerprint',
        'executable_name',
        'path_fragment',
        'launcher',
        'launcher_game_id',
        'display_name',
        'product_name',
        'total_seconds',
        'first_seen_at',
        'last_seen_at',
        'status',
        'proposed_game_id',
        'proposal_confidence',
        'igdb_game_id',
        'game_executable_mapping_id',
    ];

    /**
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'status' => 'pending',
        'total_seconds' => 0,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'launcher' => GameLauncher::class,
            'total_seconds' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'status' => MappingCandidateStatus::class,
            'proposal_confidence' => MappingConfidence::class,
            'igdb_game_id' => 'integer',
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
    public function proposedGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'proposed_game_id');
    }

    /**
     * Candidates the user should look at: still pending and actually played, not just installed.
     *
     * @param  Builder<CompanionMappingCandidate>  $query
     * @return Builder<CompanionMappingCandidate>
     */
    public function scopeAwaitingUser(Builder $query): Builder
    {
        return $query->where('status', MappingCandidateStatus::Pending)->where('total_seconds', '>', 0);
    }

    /**
     * The name shown to the user: launcher title, file description, then executable name.
     */
    public function label(): string
    {
        return $this->display_name ?? $this->product_name ?? $this->executable_name;
    }
}
