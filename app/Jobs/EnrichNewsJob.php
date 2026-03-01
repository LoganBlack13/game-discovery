<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\NewsEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class EnrichNewsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $runId
    ) {}

    public function handle(NewsEnrichmentService $service): void
    {
        $service->enrich($this->runId);
    }
}
