<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $game_id
 * @property string $title
 * @property string|null $thumbnail
 * @property string|null $source
 * @property string $url
 * @property CarbonInterface|null $published_at
 */
final class News extends Model
{
    /** @use HasFactory<NewsFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'game_id',
        'title',
        'thumbnail',
        'source',
        'url',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
