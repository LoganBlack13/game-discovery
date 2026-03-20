# Game Discovery

Game Discovery is a Laravel 12 web app for discovering upcoming and released games, tracking titles, and enriching game records with related news.

## What It Includes

- Public home feed with game discovery content
- Game detail pages (`/games/{slug}`)
- Authenticated dashboard for tracked games and personalized activity
- Admin area for:
  - adding and reviewing games
  - processing community game requests
  - running and monitoring news enrichment
- Scheduled and queued background processing for enrichment and request workflows

## Tech Stack

- PHP 8.5
- Laravel 12
- Livewire 4 + Flux UI
- Tailwind CSS v4 + DaisyUI v5
- Vite 7
- Pest 4 + PHPStan + Rector + Pint

## Prerequisites

- PHP 8.5+
- Composer
- Bun
- SQLite (default local setup) or another supported DB driver

## Local Setup

```bash
composer setup
```

`composer setup` will:

- install PHP dependencies
- create `.env` from `.env.example` when missing
- generate an app key
- run database migrations
- install frontend dependencies
- build frontend assets

## Run in Development

```bash
composer dev
```

This starts the Laravel server, queue listener, log tailing, and Vite in parallel.

## Environment Configuration

At minimum, configure these values in `.env`:

- `APP_URL`
- database connection values (if not using default sqlite setup)

For game ingestion and enrichment features, add provider credentials:

- `RAWG_API_KEY`
- `IGDB_TWITCH_CLIENT_ID`
- `IGDB_TWITCH_CLIENT_SECRET`

Optional integrations used by the app:

- `SLACK_BOT_USER_OAUTH_TOKEN`
- `SLACK_BOT_USER_DEFAULT_CHANNEL`
- `POSTMARK_TOKEN`
- `RESEND_KEY`

## Queue and Scheduler

The app uses queued jobs for enrichment and request processing. Keep a worker running in development (`composer dev` already does this).

Scheduled commands:

- `news:enrich` daily at `02:00`
- `game-requests:process` daily at `03:00`

Run them manually if needed:

```bash
php artisan news:enrich
php artisan game-requests:process --limit=5
```

## Useful Commands

### Development

- `composer dev` - Run app server, queue worker, logs, and Vite

### Formatting and Refactoring

- `composer lint` - Run Rector, Pint, and Prettier write mode
- `vendor/bin/pint --dirty --format agent` - Format only changed PHP files

### Tests and Analysis

- `php artisan test --compact` - Run test suite
- `php artisan test --compact tests/Feature/SomeTest.php` - Run one test file
- `composer test:type-coverage` - Enforce type coverage
- `composer test:types` - Run PHPStan
- `composer test` - Full quality pipeline

## Project Structure (High Level)

- `app/Http/Controllers` - web and admin controllers
- `app/Services` - game data providers, enrichment, matching, and feed services
- `app/Jobs` - async processing jobs
- `app/Console/Commands` - artisan commands for enrichment and request processing
- `resources/views` - Blade views/components (including interactive server-driven components)
- `routes/web.php` and `routes/console.php` - HTTP and scheduled command entrypoints

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
