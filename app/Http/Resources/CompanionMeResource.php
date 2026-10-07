<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CompanionDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * The account and device behind a Companion token.
 *
 * @property-read CompanionDevice $resource
 */
final class CompanionMeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $user = $this->resource->user;
        assert($user instanceof User);

        return [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'device' => [
                'id' => $this->resource->id,
                'label' => $this->resource->label,
                'platform' => $this->resource->platform,
            ],
        ];
    }
}
