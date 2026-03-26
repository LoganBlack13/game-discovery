<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProvider;
use App\Contracts\GameDataProviderResolver as GameDataProviderResolverContract;
use InvalidArgumentException;

final readonly class GameDataProviderResolver implements GameDataProviderResolverContract
{
    public function __construct(
        private RawgGameDataProvider $rawg,
        private IgdbGameDataProvider $igdb
    ) {}

    public function resolve(string $source): GameDataProvider
    {
        return match (mb_strtolower($source)) {
            'rawg' => $this->rawg,
            'igdb' => $this->igdb,
            default => throw new InvalidArgumentException(sprintf('Unknown or unsupported game data source: %s.', $source)),
        };
    }
}
