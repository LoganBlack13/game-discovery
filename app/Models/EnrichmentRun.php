<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EnrichmentRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property string $run_id
 * @property string $status
 * @property int $feeds_total
 * @property int $feeds_done
 * @property int $created_count
 * @property string|null $error
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $finished_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class EnrichmentRun extends Model
{
    /** @use HasFactory<EnrichmentRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'run_id',
        'status',
        'feeds_total',
        'feeds_done',
        'created_count',
        'error',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'feeds_total' => 'integer',
            'feeds_done' => 'integer',
            'created_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
