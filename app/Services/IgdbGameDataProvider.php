<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProvider;
use App\Enums\ReleaseStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class IgdbGameDataProvider implements GameDataProvider
{
    private const string TWITCH_TOKEN_URL = 'https://id.twitch.tv/oauth2/token';

    private const string IGDB_BASE_URL = 'https://api.igdb.com/v4';

    private const string CACHE_KEY_TOKEN = 'igdb_twitch_access_token';

    private const int TOKEN_CACHE_BUFFER_SECONDS = 60;

    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function search(string $query, ?string $platform = null, ?string $releaseStatus = null): array
    {
        $headers = $this->requestHeaders();
        if ($headers === null) {
            return [];
        }

        $body = sprintf(
            'search "%s"; fields id,name,slug,summary,first_release_date,genres.name,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,cover.image_id,platforms.name; limit 10;',
            addslashes($query)
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->withBody($body, 'text/plain')
                ->post(self::IGDB_BASE_URL.'/games');

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json();
            if (! is_array($results)) {
                return [];
            }

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
        $headers = $this->requestHeaders();
        if ($headers === null) {
            throw new InvalidArgumentException('IGDB (Twitch) credentials are not configured.');
        }

        $body = sprintf(
            'where id = %s; fields id,name,slug,summary,first_release_date,genres.name,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,cover.image_id,platforms.name;',
            (string) (int) $externalId
        );

        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->withBody($body, 'text/plain')
            ->post(self::IGDB_BASE_URL.'/games');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch game details from IGDB.');
        }

        $data = $response->json();
        if (! is_array($data) || count($data) === 0) {
            throw new RuntimeException('Game not found in IGDB.');
        }

        return $this->mapGameDetails($data[0]);
    }

    /**
     * Returns request headers for IGDB API. Uses Twitch OAuth (client_id + client_secret)
     * per official IGDB/Twitch documentation. OAuth redirect URLs in the Twitch console must be HTTPS in production.
     *
     * @return array<string, string>|null
     */
    private function requestHeaders(): ?array
    {
        $clientId = config('services.igdb.client_id');
        $clientSecret = config('services.igdb.client_secret');
        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }

        $token = Cache::get(self::CACHE_KEY_TOKEN);
        if ($token === null) {
            $token = $this->fetchAccessToken($clientId, $clientSecret);
        }

        return [
            'Client-ID' => $clientId,
            'Authorization' => 'Bearer '.$token,
        ];
    }

    private function fetchAccessToken(string $clientId, string $clientSecret): string
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post(self::TWITCH_TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch IGDB (Twitch) access token.');
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 0);

        if (empty($token)) {
            throw new RuntimeException('Invalid IGDB (Twitch) token response.');
        }

        if ($expiresIn > self::TOKEN_CACHE_BUFFER_SECONDS) {
            Cache::put(self::CACHE_KEY_TOKEN, $token, $expiresIn - self::TOKEN_CACHE_BUFFER_SECONDS);
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameFromList(array $item): array
    {
        return $this->mapGameDetails($item);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array, platforms: array, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameDetails(array $data): array
    {
        $id = (string) ($data['id'] ?? '');
        $firstReleaseDate = isset($data['first_release_date']) ? (int) $data['first_release_date'] : null;
        $releaseDate = $firstReleaseDate > 0
            ? date('Y-m-d', $firstReleaseDate)
            : null;

        $involvedCompanies = $data['involved_companies'] ?? [];
        $developer = null;
        $publisher = null;
        foreach (is_array($involvedCompanies) ? $involvedCompanies : [] as $inv) {
            if (! is_array($inv)) {
                continue;
            }
            $companyName = $inv['company']['name'] ?? null;
            if ($companyName === null) {
                continue;
            }
            if (! empty($inv['developer']) && $developer === null) {
                $developer = (string) $companyName;
            }
            if (! empty($inv['publisher']) && $publisher === null) {
                $publisher = (string) $companyName;
            }
        }

        $genres = isset($data['genres']) && is_array($data['genres'])
            ? array_values(array_map(fn (array $g): string => (string) ($g['name'] ?? ''), $data['genres']))
            : [];
        $platforms = isset($data['platforms']) && is_array($data['platforms'])
            ? array_values(array_map(fn (array $p): string => (string) ($p['name'] ?? ''), $data['platforms']))
            : [];

        $coverImageId = $data['cover']['image_id'] ?? null;
        $coverImage = $coverImageId !== null
            ? 'https://images.igdb.com/igdb/image/upload/t_cover_big/'.(string) $coverImageId.'.jpg'
            : null;

        return [
            'title' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? \Illuminate\Support\Str::slug($data['name'] ?? '')),
            'description' => isset($data['summary']) ? (string) $data['summary'] : null,
            'cover_image' => $coverImage,
            'developer' => $developer,
            'publisher' => $publisher,
            'genres' => array_filter($genres),
            'platforms' => array_filter($platforms),
            'release_date' => $releaseDate,
            'release_status' => $this->mapReleaseStatus($firstReleaseDate),
            'external_id' => $id,
            'external_source' => 'igdb',
        ];
    }

    private function mapReleaseStatus(?int $firstReleaseDate): string
    {
        if ($firstReleaseDate === null || $firstReleaseDate <= 0) {
            return ReleaseStatus::Announced->value;
        }
        $date = \Carbon\Carbon::createFromTimestamp($firstReleaseDate);

        return $date->isFuture() ? ReleaseStatus::ComingSoon->value : ReleaseStatus::Released->value;
    }
}
