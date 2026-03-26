<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProvider;
use App\Enums\ReleaseStatus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class RawgGameDataProvider implements GameDataProvider
{
    private const string BASE_URL = 'https://api.rawg.io/api';

    /** Add-game UI expects at most this many results per source. */
    private const int SEARCH_LIMIT = 10;

    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function search(string $query, ?string $platform = null, ?string $releaseStatus = null): array
    {
        $key = config('services.rawg.key');
        if (! is_string($key) || $key === '') {
            return [];
        }

        $params = [
            'key' => $key,
            'search' => $query,
            'page_size' => self::SEARCH_LIMIT,
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
            $results = is_array($data) ? ($data['results'] ?? []) : [];
            if (! is_array($results)) {
                return [];
            }

            return array_values(array_filter(array_map(
                $this->mapGameFromList(...),
                $results
            )));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    public function getGameDetails(string $externalId): array
    {
        $key = config('services.rawg.key');
        throw_if(! is_string($key) || $key === '', InvalidArgumentException::class, 'RAWG API key is not configured.');

        $response = Http::timeout(10)->get(self::BASE_URL.'/games/'.$externalId, ['key' => $key]);
        throw_unless($response->successful(), RuntimeException::class, 'Failed to fetch game details from RAWG.');

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Failed to fetch game details from RAWG.');
        }

        return $this->mapGameDetails($data);
    }

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}|null
     */
    private function mapGameFromList(mixed $item): ?array
    {
        if (! is_array($item)) { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
        }

        $id = $this->str($item['id'] ?? null);
        $released = $item['released'] ?? null;
        $developers = $item['developers'] ?? [];
        $firstDeveloper = is_array($developers) && is_array($developers[0] ?? null) && isset($developers[0]['name']) ? $developers[0]['name'] : null;
        $publishers = $item['publishers'] ?? [];
        $firstPublisher = is_array($publishers) && is_array($publishers[0] ?? null) && isset($publishers[0]['name']) ? $publishers[0]['name'] : null;
        $genres = isset($item['genres']) && is_array($item['genres'])
            ? array_column($item['genres'], 'name')
            : [];
        $platforms = isset($item['platforms']) && is_array($item['platforms'])
            ? array_values(array_map(function (mixed $p): string {
                if (is_array($p)) {
                    $platform = $p['platform'] ?? null;
                    if (is_array($platform) && isset($platform['name'])) {
                        return $this->str($platform['name']);
                    }

                    if (isset($p['name'])) {
                        return $this->str($p['name']);
                    }
                }

                return '';
            }, $item['platforms']))
            : [];

        $name = $this->str($item['name'] ?? null);

        return [
            'title' => $name,
            'slug' => $this->str($item['slug'] ?? null) ?: Str::slug($name),
            'description' => null,
            'cover_image' => is_string($item['background_image'] ?? null) && $item['background_image'] !== '' ? $item['background_image'] : null,
            'developer' => $firstDeveloper !== null ? $this->str($firstDeveloper) : null,
            'publisher' => $firstPublisher !== null ? $this->str($firstPublisher) : null,
            'genres' => array_values(array_filter(array_map(fn (mixed $g): string => $this->str($g), $genres))),
            'platforms' => array_values(array_filter($platforms)),
            'release_date' => is_string($released) && $released !== '' ? $released : null,
            'release_status' => $this->mapReleaseStatus($released, (bool) ($item['tba'] ?? false)),
            'external_id' => $id,
            'external_source' => 'rawg',
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameDetails(array $data): array
    {
        $id = $this->str($data['id'] ?? null);
        $released = $data['released'] ?? null;
        $developers = $data['developers'] ?? [];
        $firstDeveloper = is_array($developers) && is_array($developers[0] ?? null) && isset($developers[0]['name']) ? $developers[0]['name'] : null;
        $publishers = $data['publishers'] ?? [];
        $firstPublisher = is_array($publishers) && is_array($publishers[0] ?? null) && isset($publishers[0]['name']) ? $publishers[0]['name'] : null;
        $genres = isset($data['genres']) && is_array($data['genres'])
            ? array_column($data['genres'], 'name')
            : [];
        $platforms = isset($data['platforms']) && is_array($data['platforms'])
            ? array_values(array_map(function (mixed $p): string {
                if (is_array($p)) {
                    $platform = $p['platform'] ?? null;

                    return is_array($platform) ? $this->str($platform['name'] ?? null) : '';
                }

                return '';
            }, $data['platforms']))
            : [];

        $name = $this->str($data['name'] ?? null);

        return [
            'title' => $name,
            'slug' => $this->str($data['slug'] ?? null) ?: Str::slug($name),
            'description' => isset($data['description_raw']) ? $this->str($data['description_raw']) : null,
            'cover_image' => is_string($data['background_image'] ?? null) && $data['background_image'] !== '' ? $data['background_image'] : null,
            'developer' => $firstDeveloper !== null ? $this->str($firstDeveloper) : null,
            'publisher' => $firstPublisher !== null ? $this->str($firstPublisher) : null,
            'genres' => array_values(array_filter(array_map(fn (mixed $g): string => $this->str($g), $genres))),
            'platforms' => array_values(array_filter($platforms)),
            'release_date' => is_string($released) && $released !== '' ? $released : null,
            'release_status' => $this->mapReleaseStatus($released, (bool) ($data['tba'] ?? false)),
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

        $date = Date::parse($this->str($released));

        return $date->isFuture() ? ReleaseStatus::ComingSoon->value : ReleaseStatus::Released->value;
    }

    private function str(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
