<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GameDataProvider;
use App\Enums\ReleaseStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class IgdbGameDataProvider implements GameDataProvider
{
    private const string TWITCH_TOKEN_URL = 'https://id.twitch.tv/oauth2/token';

    private const string IGDB_BASE_URL = 'https://api.igdb.com/v4';

    private const string CACHE_KEY_TOKEN = 'igdb_twitch_access_token';

    private const int TOKEN_CACHE_BUFFER_SECONDS = 60;

    /** Add-game UI expects at most this many results per source. */
    private const int SEARCH_LIMIT = 10;

    /**
     * @return array<int, array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}>
     */
    public function search(string $query, ?string $platform = null, ?string $releaseStatus = null): array
    {
        $headers = $this->requestHeaders();
        if ($headers === null) {
            return [];
        }

        $body = sprintf(
            'search "%s"; fields id,name,slug,summary,first_release_date,genres.name,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,cover.image_id,platforms.name; limit %d;',
            addslashes($query),
            self::SEARCH_LIMIT
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->withBody($body, 'text/plain')
                ->post(self::IGDB_BASE_URL.'/games');

            if (! $response->successful()) { // @codeCoverageIgnore
                return []; // @codeCoverageIgnore
            }

            $results = $response->json();
            if (! is_array($results)) { // @codeCoverageIgnore
                return []; // @codeCoverageIgnore
            }

            return array_values(array_filter(array_map(
                $this->mapGameFromList(...),
                $results
            )));
        } catch (Throwable) { // @codeCoverageIgnore
            return []; // @codeCoverageIgnore
        }
    }

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    public function getGameDetails(string $externalId): array
    {
        $headers = $this->requestHeaders();
        throw_if($headers === null, InvalidArgumentException::class, 'IGDB (Twitch) credentials are not configured.');

        $body = sprintf(
            'where id = %s; fields id,name,slug,summary,first_release_date,genres.name,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,cover.image_id,platforms.name;',
            (string) (int) $externalId
        );

        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->withBody($body, 'text/plain')
            ->post(self::IGDB_BASE_URL.'/games');

        if (! $response->successful()) { // @codeCoverageIgnore
            throw new RuntimeException('Failed to fetch game details from IGDB.'); // @codeCoverageIgnore
        }

        $data = $response->json();
        if (! is_array($data) || $data === []) { // @codeCoverageIgnore
            throw new RuntimeException('Game not found in IGDB.'); // @codeCoverageIgnore
        }

        $first = $data[0];
        if (! is_array($first)) { // @codeCoverageIgnore
            throw new RuntimeException('Game not found in IGDB.'); // @codeCoverageIgnore
        }

        return $this->mapGameDetails($first);
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
        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            return null;
        }

        /** @var string|null $token */
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

        if (! $response->successful()) { // @codeCoverageIgnore
            throw new RuntimeException('Failed to fetch IGDB (Twitch) access token.'); // @codeCoverageIgnore
        }

        $data = $response->json();
        if (! is_array($data)) { // @codeCoverageIgnore
            throw new RuntimeException('Invalid IGDB (Twitch) token response.'); // @codeCoverageIgnore
        }

        $token = $data['access_token'] ?? null;
        $rawExpires = $data['expires_in'] ?? null;
        $expiresIn = is_numeric($rawExpires) ? (int) $rawExpires : 0;

        if (! is_string($token) || empty($token)) { // @codeCoverageIgnore
            throw new RuntimeException('Invalid IGDB (Twitch) token response.'); // @codeCoverageIgnore
        }

        if ($expiresIn > self::TOKEN_CACHE_BUFFER_SECONDS) {
            Cache::put(self::CACHE_KEY_TOKEN, $token, $expiresIn - self::TOKEN_CACHE_BUFFER_SECONDS);
        }

        return $token;
    }

    /**
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}|null
     */
    private function mapGameFromList(mixed $item): ?array
    {
        if (! is_array($item)) { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
        }

        return $this->mapGameDetails($item);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array{title: string, slug: string, description: string|null, cover_image: string|null, developer: string|null, publisher: string|null, genres: array<string>, platforms: array<string>, release_date: string|null, release_status: string, external_id: string, external_source: string}
     */
    private function mapGameDetails(array $data): array
    {
        $id = $this->str($data['id'] ?? null);
        $rawRelease = $data['first_release_date'] ?? null;
        $firstReleaseDate = is_numeric($rawRelease) ? (int) $rawRelease : null;
        $releaseDate = $firstReleaseDate > 0
            ? date('Y-m-d', $firstReleaseDate)
            : null;

        $involvedCompanies = $data['involved_companies'] ?? [];
        $developer = null;
        $publisher = null;
        foreach (is_array($involvedCompanies) ? $involvedCompanies : [] as $inv) {
            if (! is_array($inv)) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            $company = $inv['company'] ?? null;
            $companyData = is_array($company) ? $company : [];
            $companyName = $companyData['name'] ?? null;
            if ($companyName === null) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            if (! empty($inv['developer']) && $developer === null) {
                $developer = $this->str($companyName);
            }

            if (! empty($inv['publisher']) && $publisher === null) {
                $publisher = $this->str($companyName);
            }
        }

        $genres = isset($data['genres']) && is_array($data['genres'])
            ? array_values(array_map(fn (mixed $g): string => is_array($g) ? $this->str($g['name'] ?? null) : '', $data['genres']))
            : [];
        $platforms = isset($data['platforms']) && is_array($data['platforms'])
            ? array_values(array_map(fn (mixed $p): string => is_array($p) ? $this->str($p['name'] ?? null) : '', $data['platforms']))
            : [];

        $coverData = is_array($data['cover'] ?? null) ? $data['cover'] : [];
        $coverImageId = $coverData['image_id'] ?? null;
        $coverImage = is_string($coverImageId) && $coverImageId !== ''
            ? 'https://images.igdb.com/igdb/image/upload/t_cover_big/'.$coverImageId.'.jpg'
            : null;

        $name = $this->str($data['name'] ?? null);

        return [
            'title' => $name,
            'slug' => $this->str($data['slug'] ?? null) ?: Str::slug($name),
            'description' => isset($data['summary']) ? $this->str($data['summary']) : null,
            'cover_image' => $coverImage,
            'developer' => $developer,
            'publisher' => $publisher,
            'genres' => array_values(array_filter($genres)),
            'platforms' => array_values(array_filter($platforms)),
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

        $date = Date::createFromTimestamp($firstReleaseDate);

        return $date->isFuture() ? ReleaseStatus::ComingSoon->value : ReleaseStatus::Released->value;
    }

    private function str(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
