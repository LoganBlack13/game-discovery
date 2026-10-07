<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameLauncher;
use App\Enums\MappingConfidence;
use Carbon\CarbonInterface;
use Database\Factories\GameExecutableMappingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Tells the Companion which executable belongs to which game. Without a user it is shared by everyone.
 *
 * @property int $id
 * @property int $game_id
 * @property string|null $user_id
 * @property string $executable_name
 * @property string|null $path_fragment
 * @property GameLauncher|null $launcher
 * @property string|null $launcher_game_id
 * @property MappingConfidence $confidence
 * @property bool $validated_by_user
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class GameExecutableMapping extends Model
{
    /** @use HasFactory<GameExecutableMappingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'game_id',
        'user_id',
        'executable_name',
        'path_fragment',
        'launcher',
        'launcher_game_id',
        'confidence',
        'validated_by_user',
    ];

    /**
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'validated_by_user' => false,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'launcher' => GameLauncher::class,
            'confidence' => MappingConfidence::class,
            'validated_by_user' => 'boolean',
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
     * Mappings of the user plus the shared ones.
     *
     * @param  Builder<GameExecutableMapping>  $query
     * @return Builder<GameExecutableMapping>
     */
    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    /**
     * Two mappings with the same key target the same executable; a personal one overrides a shared one.
     */
    public function matchKey(): string
    {
        if ($this->launcher instanceof GameLauncher && $this->launcher_game_id !== null) {
            return "launcher:{$this->launcher->value}:{$this->launcher_game_id}";
        }

        return 'exe:'.mb_strtolower($this->executable_name).':'.mb_strtolower((string) $this->path_fragment);
    }
}
