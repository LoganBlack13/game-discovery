<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $image
 * @property string|null $external_id
 * @property string|null $external_source
 */
final class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'name',
        'slug',
        'image',
        'external_id',
        'external_source',
    ];

    /**
     * @return HasMany<GameCredit, $this>
     */
    public function credits(): HasMany
    {
        return $this->hasMany(GameCredit::class);
    }
}
