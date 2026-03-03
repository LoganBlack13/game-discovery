<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\GameRequestProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

final class ProcessGameRequestsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $runId = null,
        public int $limit = 5
    ) {}

    public function handle(GameRequestProcessorService $service): void
    {
        $runId = $this->runId ?? Str::uuid()->toString();
        $this->runId = $runId;

        $service->process($this->limit, $runId);
    }
}
