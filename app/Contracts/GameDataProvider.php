<?php

declare(strict_types=1);

namespace App\Contracts;

interface GameDataProvider
{
    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function search(string $query, ?string $platform = null, ?string $releaseStatus = null): array;

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    public function getGameDetails(string $externalId): array;
}
