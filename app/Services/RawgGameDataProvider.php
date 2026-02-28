<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProvider;
use App\Enums\ReleaseStatus;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class RawgGameDataProvider implements GameDataProvider
{
    private const string BASE_URL = 'https://api.rawg.io/api';

    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function search(string $query, ?string $platform = null, ?string $releaseStatus = null): array
    {
        $key = config('services.rawg.key');
        if (empty($key)) {
            return [];
        }

        $params = [
            'key' => $key,
            'search' => $query,
            'page_size' => 10,
        ];
        if ($platform !== null) {
            $params['platforms'] = $platform;
        }

        try {
            $response = Http::timeout(10)->get(self::BASE_URL.'/games', $params);
            if (! $response->successful()) {
                return [];
            }
            $data = $response->json();
            $results = $data['results'] ?? [];

            return array_values(array_filter(array_map(
                fn (array $item): array => $this->mapGameFromList($item),
                $results
            )));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    public function getGameDetails(string $externalId): array
    {
        $key = config('services.rawg.key');
        if (empty($key)) {
            throw new InvalidArgumentException('RAWG API key is not configured.');
        }

        $response = Http::timeout(10)->get(self::BASE_URL.'/games/'.$externalId, ['key' => $key]);
        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch game details from RAWG.');
        }

        $data = $response->json();

        return $this->mapGameDetails($data);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameFromList(array $item): array
    {
        $id = (string) ($item['id'] ?? '');
        $released = $item['released'] ?? null;
        $developers = $item['developers'] ?? [];
        $firstDeveloper = is_array($developers) && isset($developers[0]['name']) ? $developers[0]['name'] : null;
        $publishers = $item['publishers'] ?? [];
        $firstPublisher = is_array($publishers) && isset($publishers[0]['name']) ? $publishers[0]['name'] : null;
        $genres = isset($item['genres']) && is_array($item['genres'])
            ? array_column($item['genres'], 'name')
            : [];
        $platforms = isset($item['platforms']) && is_array($item['platforms'])
            ? array_values(array_map(function (mixed $p): string {
                if (is_array($p) && isset($p['platform']['name'])) {
                    return (string) $p['platform']['name'];
                }
                if (is_array($p) && isset($p['name'])) {
                    return (string) $p['name'];
                }

                return '';
            }, $item['platforms']))
            : [];

        return [
            'title' => (string) ($item['name'] ?? ''),
            'slug' => (string) ($item['slug'] ?? \Illuminate\Support\Str::slug($item['name'] ?? '')),
            'description' => null,
            'cover_image' => $item['background_image'] ?? null ? (string) $item['background_image'] : null,
            'developer' => $firstDeveloper !== null ? (string) $firstDeveloper : null,
            'publisher' => $firstPublisher !== null ? (string) $firstPublisher : null,
            'genres' => $genres,
            'platforms' => array_filter($platforms),
            'release_date' => $released ? (string) $released : null,
            'release_status' => $this->mapReleaseStatus($item['released'] ?? null, $item['tba'] ?? false),
            'external_id' => $id,
            'external_source' => 'rawg',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameDetails(array $data): array
    {
        $id = (string) ($data['id'] ?? '');
        $released = $data['released'] ?? null;
        $developers = $data['developers'] ?? [];
        $firstDeveloper = is_array($developers) && isset($developers[0]['name']) ? $developers[0]['name'] : null;
        $publishers = $data['publishers'] ?? [];
        $firstPublisher = is_array($publishers) && isset($publishers[0]['name']) ? $publishers[0]['name'] : null;
        $genres = isset($data['genres']) && is_array($data['genres'])
            ? array_column($data['genres'], 'name')
            : [];
        $platforms = isset($data['platforms']) && is_array($data['platforms'])
            ? array_values(array_map(fn (array $p): string => $p['platform']['name'] ?? '', $data['platforms']))
            : [];

        return [
            'title' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? \Illuminate\Support\Str::slug($data['name'] ?? '')),
            'description' => isset($data['description_raw']) ? (string) $data['description_raw'] : null,
            'cover_image' => $data['background_image'] ?? null ? (string) $data['background_image'] : null,
            'developer' => $firstDeveloper !== null ? (string) $firstDeveloper : null,
            'publisher' => $firstPublisher !== null ? (string) $firstPublisher : null,
            'genres' => $genres,
            'platforms' => array_filter($platforms),
            'release_date' => $released ? (string) $released : null,
            'release_status' => $this->mapReleaseStatus($released, $data['tba'] ?? false),
            'external_id' => $id,
            'external_source' => 'rawg',
        ];
    }

    private function mapReleaseStatus(mixed $released, bool $tba): string
    {
        if ($tba) {
            return ReleaseStatus::ComingSoon->value;
        }
        if (empty($released)) {
            return ReleaseStatus::Announced->value;
        }
        $date = \Carbon\Carbon::parse((string) $released);

        return $date->isFuture() ? ReleaseStatus::ComingSoon->value : ReleaseStatus::Released->value;
    }
}
