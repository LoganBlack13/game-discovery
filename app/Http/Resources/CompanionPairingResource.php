<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CompanionPairing;
use App\Services\CompanionPairingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read CompanionPairing $resource
 */
final class CompanionPairingResource extends JsonResource
{
    public function __construct(CompanionPairing $resource, private readonly string $secret)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'pairing_id' => $this->resource->id,
            'user_code' => $this->resource->displayCode(),
            'pairing_secret' => $this->secret,
            'verification_url' => route('companion.link', ['code' => $this->resource->displayCode()]),
            'expires_at' => $this->resource->expires_at->toIso8601ZuluString(),
            'interval' => CompanionPairingService::POLL_INTERVAL_SECONDS,
        ];
    }
}
