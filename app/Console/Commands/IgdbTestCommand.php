<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IgdbGameDataProvider;
use Illuminate\Console\Command;
use Throwable;

final class IgdbTestCommand extends Command
{
    protected $signature = 'igdb:test {query=subnautica 2 : Search term to test IGDB}';

    protected $description = 'Test IGDB API connection and search (Twitch OAuth). Use this to confirm IGDB returns results.';

    public function handle(IgdbGameDataProvider $provider): int
    {
        $query = (string) $this->argument('query');

        $clientId = config('services.igdb.client_id');
        $clientSecret = config('services.igdb.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->error('IGDB credentials not set. Set IGDB_TWITCH_CLIENT_ID and IGDB_TWITCH_CLIENT_SECRET in .env');
            $this->line('If they are already in .env, run: php artisan config:clear');
            $this->line('Ensure .env ends with a newline after the last variable (some parsers skip the last line otherwise).');

            return self::FAILURE;
        }

        $this->info('IGDB credentials configured. Searching for: '.$query);
        $this->newLine();

        try {
            $results = $provider->search($query);
        } catch (Throwable $e) {
            $this->error('IGDB request failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($results === []) {
            $this->warn('IGDB returned 0 results. Check your credentials or try another search term.');

            return self::SUCCESS;
        }

        $this->info('IGDB returned '.count($results).' result(s):');
        $this->newLine();

        foreach ($results as $i => $item) {
            $this->line(sprintf(
                '  %d. %s (id: %s)',
                $i + 1,
                $item['title'],
                $item['external_id']
            ));
            if (! empty($item['release_date'])) {
                $this->line('     Release: '.$item['release_date']);
            }
        }

        return self::SUCCESS;
    }
}
