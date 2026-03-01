# Video Game News Enrichment Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task.

**Goal:** Enrich the app with video game news from RSS feeds. News is stored only for games that exist in the database. Enrichment can run on a schedule (Artisan command + Laravel scheduler) or be triggered manually from the admin dashboard. The admin UI shows live progress: which RSS feed is being crawled and which game news is being retrieved in real time.

**Architecture:** A configurable list of RSS feed URLs (config file). A service fetches and parses RSS (HTTP + SimpleXML); a matcher associates RSS item titles with games in the DB (substring match, longest match first). An enrichment job processes feeds one by one, updates progress in cache (run_id-scoped), and creates `News` records only for matched games and only when the URL is not already stored. An Artisan command invokes the same logic for scheduling. Admin: a dedicated “News enrichment” page with a Livewire component that starts a run (dispatches the job with a new run_id), polls an endpoint for progress every 1–2s, and displays a live log (current feed, matched items). No new major dependencies: use Laravel HTTP client and native XML for RSS.

**Tech Stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest, Laravel Cache (file/redis) for progress. No new Composer packages required.

**RSS sources (from [FeedSpot Video Game RSS](https://rss.feedspot.com/video_game_rss_feeds/)):** GameSpot, Game Informer, IGN, Polygon, plus additional feeds listed in config (e.g. VG247, PC Gamer, Rock Paper Shotgun, Nintendo Life, Gematsu, Eurogamer, etc.).

---

## Out of scope / assumptions

- No change to `News` schema except possibly a unique index on `(game_id, url)` to enforce deduplication at DB level (optional; deduplication is done in code).
- Matching is title-based only (game title appears in news title); no ML or external APIs for matching.
- Progress is stored in cache with TTL (e.g. 1 hour) so stale runs do not linger.

---

## Task 1: Config and RSS feed list

**Files:**
- Create: `config/news_enrichment.php`
- Modify: (none)

**Step 1: Add config file**

Create `config/news_enrichment.php` that returns:

- `feeds`: array of feed entries. Each entry: `name` (string, display name), `url` (string). Include at least: GameSpot (`https://www.gamespot.com/feeds/mashup`), Game Informer (`https://www.gameinformer.com/rss.xml`), IGN (use feed URL from FeedSpot or common pattern e.g. `https://feeds.feedburner.com/ign/all`), Polygon (`https://www.polygon.com/rss/index.xml`). Add 5–10 more from FeedSpot list (e.g. VG247, PC Gamer, Rock Paper Shotgun, Nintendo Life, Gematsu, Eurogamer, Destructoid, Kotaku) so the list is usable out of the box.
- `progress_ttl_seconds`: int (e.g. 3600) for cache TTL of progress payload.

**Step 2: Register and use config**

Ensure config is loaded (Laravel auto-loads `config/*.php`). Reference in code as `config('news_enrichment.feeds')` and `config('news_enrichment.progress_ttl_seconds')`.

**Step 3: Commit**

```bash
git add config/news_enrichment.php
git commit -m "config: add news enrichment RSS feed list and progress TTL"
```

---

## Task 2: RSS fetcher service

**Files:**
- Create: `app/Services/RssFeedFetcher.php`
- Test: `tests/Unit/Services/RssFeedFetcherTest.php`

**Step 1: Write failing test**

Create `tests/Unit/Services/RssFeedFetcherTest.php`. Test: given a known public RSS URL (e.g. a small feed or use Http::fake() with a stub XML response), `RssFeedFetcher::fetch(url)` returns an array of items; each item has at least `title`, `url` (link), `published_at` (Carbon or null). Optionally test that invalid XML or failed request returns empty array or throws. Use Http::fake() to avoid real network in unit test.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Unit/Services/RssFeedFetcherTest.php
```

Expected: FAIL (class/method not found).

**Step 3: Implement RssFeedFetcher**

Create `app/Services/RssFeedFetcher.php`. Use `Illuminate\Support\Facades\Http::get($url)` to fetch; parse response body with `SimpleXML` or `simplexml_load_string`. Map `<item>` elements to array with `title`, `url` (from `link` or `guid`), `published_at` (parse RSS `pubDate`), optionally `thumbnail` (media or enclosure if present). Handle errors (non-200, invalid XML) by returning empty array or throwing a dedicated exception; catch in caller. Return type: `array<int, array{title: string, url: string, published_at: \Carbon\Carbon|null, thumbnail: string|null}>`.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Unit/Services/RssFeedFetcherTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/RssFeedFetcher.php tests/Unit/Services/RssFeedFetcherTest.php
git commit -m "feat: add RssFeedFetcher service and unit tests"
```

---

## Task 3: Game–news matcher service

**Files:**
- Create: `app/Services/NewsGameMatcher.php`
- Test: `tests/Unit/Services/NewsGameMatcherTest.php`

**Step 1: Write failing test**

Create `tests/Unit/Services/NewsGameMatcherTest.php`. Use RefreshDatabase and Game factory. Test: `findMatchingGame(string $newsTitle)` returns a Game when a game’s title appears in `$newsTitle` (e.g. game “Elden Ring”, news title “Elden Ring DLC announced”); returns null when no game title matches; when multiple games could match (e.g. “Ring” and “Elden Ring”), the longest matching title wins. Add a test that ensures we only consider games that exist in DB (no fake IDs).

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Unit/Services/NewsGameMatcherTest.php
```

Expected: FAIL.

**Step 3: Implement NewsGameMatcher**

Create `app/Services/NewsGameMatcher.php`. Method `findMatchingGame(string $newsTitle): ?Game`. Load games (e.g. `Game::query()->get(['id','title'])`); sort by title length descending so longer titles are matched first. Loop and return first game where `stripos($newsTitle, $game->title) !== false`. Return null if none. Optional: normalize whitespace and trim for robustness.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Unit/Services/NewsGameMatcherTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/NewsGameMatcher.php tests/Unit/Services/NewsGameMatcherTest.php
git commit -m "feat: add NewsGameMatcher service and unit tests"
```

---

## Task 4: Enrichment service (core logic)

**Files:**
- Create: `app/Services/NewsEnrichmentService.php`
- Test: `tests/Feature/Services/NewsEnrichmentServiceTest.php` or Unit with DB

**Step 1: Write failing test**

Create a feature or unit test that: creates 1–2 games via factory; mocks or fakes `RssFeedFetcher` to return a few items (one title containing a game title, one not); calls `NewsEnrichmentService::enrich(string $runId)` or similar with a callback/progress writer that the test can inspect. Assert that one News record is created for the matching game with correct url/title/source; the non-matching item creates no News. Assert that calling enrich again with same data does not create duplicate News (same game_id + url). Use a small config override or in-memory feed list for tests.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/Services/NewsEnrichmentServiceTest.php
```

Expected: FAIL.

**Step 3: Implement NewsEnrichmentService**

Create `app/Services/NewsEnrichmentService.php`. Constructor inject `RssFeedFetcher`, `NewsGameMatcher`, and optionally `Cache` (or use `Illuminate\Support\Facades\Cache`). Method `enrich(string $runId, ?callable $progressCallback = null): void` (or accept a progress writer interface). For each feed in `config('news_enrichment.feeds')`: (1) write progress to cache: current feed name/url, status `running`. (2) Fetch items via `RssFeedFetcher::fetch($feedUrl)`. (3) For each item, call `NewsGameMatcher::findMatchingGame($item['title'])`. If game found, check `News::where('game_id', $game->id)->where('url', $item['url'])->exists()`; if not exists, create `News::create([...])` with game_id, title, url, source (feed name), published_at, thumbnail. (4) Invoke progress callback or write to cache: e.g. “Matched: {game title} – {news title}”. After all feeds, set progress status `completed` and store summary (e.g. created_count, feeds_processed). Progress cache key: e.g. `news_enrichment:progress:{runId}` with TTL from config.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/Services/NewsEnrichmentServiceTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/NewsEnrichmentService.php tests/Feature/Services/NewsEnrichmentServiceTest.php
git commit -m "feat: add NewsEnrichmentService with progress and deduplication"
```

---

## Task 5: Enrichment job (queue)

**Files:**
- Create: `app/Jobs/EnrichNewsJob.php`
- Test: `tests/Feature/Jobs/EnrichNewsJobTest.php`

**Step 1: Write failing test**

In `tests/Feature/Jobs/EnrichNewsJobTest.php`: dispatch `EnrichNewsJob` with a run_id; run the job (e.g. `$job->handle(...)` or queue sync); assert that progress in cache for that run_id has status `completed` and that News records were created when applicable (use faked fetcher or small feed config).

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/Jobs/EnrichNewsJobTest.php
```

Expected: FAIL.

**Step 3: Implement EnrichNewsJob**

Create `app/Jobs/EnrichNewsJob.php` implementing `ShouldQueue`. Constructor: `public function __construct(public string $runId)`. In `handle(NewsEnrichmentService $service)`, call `$service->enrich($this->runId)`. The service should read/write progress via cache so the job does not need to pass a callback (progress is read by the admin UI by run_id). Ensure the service writes progress to cache at each step (current feed, each matched news) so the UI can poll and display it.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/Jobs/EnrichNewsJobTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Jobs/EnrichNewsJob.php tests/Feature/Jobs/EnrichNewsJobTest.php
git commit -m "feat: add EnrichNewsJob for queued news enrichment"
```

---

## Task 6: Artisan command and scheduling

**Files:**
- Create: `app/Console/Commands/EnrichNewsCommand.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Console/EnrichNewsCommandTest.php`

**Step 1: Write failing test**

Create `tests/Feature/Console/EnrichNewsCommandTest.php`. Run `php artisan news:enrich` (or chosen name); assert exit code 0 and output contains success or summary; optionally assert progress was written to cache with a run_id (command can generate run_id internally). Use faked/small feed config to avoid long runs.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/Console/EnrichNewsCommandTest.php
```

Expected: FAIL.

**Step 3: Create command**

Run:

```bash
php artisan make:command EnrichNewsCommand --no-interaction
```

Implement: generate a run_id (e.g. `Str::uuid()->toString()`), call `NewsEnrichmentService::enrich($runId)` (sync), then output summary from progress cache (e.g. “Enriched X feeds, created Y news items”). Command name: `news:enrich`.

**Step 4: Schedule the command**

In `routes/console.php`, use `Illuminate\Support\Facades\Schedule`. Add e.g. `Schedule::command('news:enrich')->daily()->at('02:00');` (or hourly if preferred). Laravel 11+ uses `routes/console.php` for scheduling; ensure the schedule is run by the cron entry `* * * * * php artisan schedule:run`.

**Step 5: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/Console/EnrichNewsCommandTest.php
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Console/Commands/EnrichNewsCommand.php routes/console.php tests/Feature/Console/EnrichNewsCommandTest.php
git commit -m "feat: add news:enrich command and schedule"
```

---

## Task 7: Progress API for admin (run_id-scoped)

**Files:**
- Create: `app/Http/Controllers/Admin/NewsEnrichmentProgressController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/NewsEnrichmentProgressTest.php`

**Step 1: Write failing test**

Test that GET `/admin/news-enrichment/progress?run_id=...` returns JSON with progress payload (status, current_feed_name, current_feed_url, feeds_done, feeds_total, last_matched entries, created_count). When run_id has no cache entry, return 404 or empty payload. Require admin auth and middleware.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/Admin/NewsEnrichmentProgressTest.php
```

Expected: FAIL.

**Step 3: Implement controller and route**

Create `NewsEnrichmentProgressController`. Method `__invoke(Request $request)`: validate `run_id` (required, string). Get cache key `news_enrichment:progress:{run_id}`; if missing, return response with 404 or `['status' => 'not_found']`. Return JSON of the cached progress structure so the frontend can display current feed, list of “Matched: game – title” and created_count.

**Step 4: Add route**

In `routes/web.php`, inside the admin group: `Route::get('/news-enrichment/progress', [NewsEnrichmentProgressController::class, '__invoke'])->name('news-enrichment.progress');`. Use same middleware (auth, verified, admin).

**Step 5: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/Admin/NewsEnrichmentProgressTest.php
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/NewsEnrichmentProgressController.php routes/web.php tests/Feature/Admin/NewsEnrichmentProgressTest.php
git commit -m "feat: add admin news enrichment progress API"
```

---

## Task 8: Admin “News enrichment” page and Livewire live progress

**Files:**
- Create: `resources/views/admin/news-enrichment.blade.php`
- Create: `resources/views/components/⚡admin-news-enrichment.blade.php` (Livewire)
- Modify: `routes/web.php`
- Modify: `resources/views/admin/dashboard.blade.php` (link to new page)

**Step 1: Add route and view**

In `routes/web.php` (admin group): `Route::get('/news-enrichment', fn () => view('admin.news-enrichment'))->name('news-enrichment');`. Create `resources/views/admin/news-enrichment.blade.php` using `<x-layouts.admin title="News enrichment">` and include `<livewire:admin-news-enrichment />`. Add a link from admin dashboard (e.g. card or nav) to `route('admin.news-enrichment')`.

**Step 2: Create Livewire component**

Create anonymous Livewire component `⚡admin-news-enrichment.blade.php`. State: `$runId = null`, `$status = 'idle'` (idle|running|completed|failed), `$progress = []` (decoded from progress API). Button “Run enrichment”: when clicked, generate new run_id (e.g. `Str::uuid()->toString()`), store in component, dispatch `EnrichNewsJob::dispatch($runId)`, set status to `running`. Use `wire:poll.2s="refreshProgress"` when `$runId` is set and status is `running`. Method `refreshProgress()`: if `$runId` is null, return; GET the progress endpoint (use `Http::get(route('admin.news-enrichment.progress', ['run_id' => $this->runId]))` or Livewire’s `$this->runId` in a request from the frontend). Parse JSON and set `$progress` and `$status`. When status is `completed` or `failed`, stop polling (conditional wire:poll: only poll when status === 'running').

**Step 3: Template – live log**

Display: when idle, show “Run enrichment” button and short description (e.g. “Crawl configured RSS feeds and attach news only to games in the database.”). When running: show “Crawling: {current_feed_name}” and a list of “Matched: {game_title} – {news_title}” (from progress.last_matched or equivalent). When completed: show summary (e.g. “Created X news items from Y feeds”) and optionally a “Run again” button. When failed: show error message. Use `aria-live="polite"` for the log area so screen readers get updates. Style with Flux/Tailwind consistent with admin (zinc, dark mode).

**Step 4: Verify manually**

As admin, open `/admin/news-enrichment`, click “Run enrichment”, confirm job runs (queue worker must be running or use sync driver for test) and UI updates with current feed and matched items. If queue is async, ensure worker is running so the job executes and updates cache.

**Step 5: Commit**

```bash
git add resources/views/admin/news-enrichment.blade.php resources/views/components/⚡admin-news-enrichment.blade.php routes/web.php resources/views/admin/dashboard.blade.php
git commit -m "feat: admin news enrichment page with live progress"
```

---

## Task 9: Wire progress from EnrichNewsJob into cache

**Files:**
- Modify: `app/Services/NewsEnrichmentService.php`
- Modify: `app/Jobs/EnrichNewsJob.php` (if needed)

**Step 1: Ensure progress structure is consistent**

Progress payload in cache should include: `status` (running|completed|failed), `current_feed_name`, `current_feed_url`, `feeds_total`, `feeds_done`, `last_matched` (array of e.g. `['game_title' => string, 'news_title' => string]` or similar), `created_count`, `error` (optional, for failed). `NewsEnrichmentService` should write this structure at the start of each feed and after each matched item (append to `last_matched` with a reasonable limit, e.g. last 50). On completion, set status and summary.

**Step 2: Run tests**

```bash
php artisan test --compact --filter=EnrichNews
```

Expected: all related tests pass.

**Step 3: Commit**

```bash
git add app/Services/NewsEnrichmentService.php app/Jobs/EnrichNewsJob.php
git commit -m "fix: ensure enrichment progress structure for admin UI"
```

---

## Task 10: Optional – unique index on news (game_id, url)

**Files:**
- Create: `database/migrations/xxxx_add_unique_news_game_url.php`
- Modify: (none)

**Step 1: Migration**

Create migration that adds a unique index on `news(game_id, url)` so duplicate inserts are prevented at DB level. Use `$table->unique(['game_id', 'url']);`. In `down()`, drop the unique index.

**Step 2: Run migration**

```bash
php artisan migrate
```

**Step 3: Commit**

```bash
git add database/migrations/xxxx_add_unique_news_game_url.php
git commit -m "feat: unique index on news (game_id, url)"
```

---

## Task 11: Documentation and final checks

**Files:**
- Modify: `README.md` or docs (only if project explicitly documents features – see rules: “only create documentation files if explicitly requested”)
- Run: `vendor/bin/pint --dirty`
- Run: full test suite for touched areas

**Step 1: Pint and tests**

Run `vendor/bin/pint --dirty`. Run `php artisan test --compact` for all tests under Feature/Admin, Feature/Console, Feature/Jobs, Feature/Services, Unit/Services.

**Step 2: Manual checklist**

- Config has at least 8–10 RSS feed URLs from FeedSpot list.
- Command `php artisan news:enrich` runs and creates news for games in DB.
- Schedule is defined in `routes/console.php`.
- Admin page shows live progress when enrichment runs (with queue worker).
- News are only for games in DB; no duplicate (game_id, url).

**Step 3: Commit**

```bash
git add -A
git commit -m "chore: pint and final news enrichment checks"
```

---

## RSS feed URLs reference (FeedSpot)

Use these in `config/news_enrichment.php` (name + url):

| Name           | URL |
|----------------|-----|
| GameSpot       | https://www.gamespot.com/feeds/mashup |
| Game Informer  | https://www.gameinformer.com/rss.xml |
| IGN            | https://feeds.feedburner.com/ign/all (or current IGN feed) |
| Polygon        | https://www.polygon.com/rss/index.xml |
| VG247          | https://www.vg247.com/feed |
| PC Gamer       | https://www.pcgamer.com/rss |
| Rock Paper Shotgun | https://www.rockpapershotgun.com/feed |
| Nintendo Life  | https://www.nintendolife.com/feeds/latest |
| Gematsu        | https://www.gematsu.com/feed |
| Kotaku         | https://kotaku.com/rss |
| Eurogamer      | https://www.eurogamer.net/?format=rss (verify URL) |
| Destructoid    | https://www.destructoid.com/feed |

Verify URLs at implementation time; some sites may have changed paths.

---

## Execution handoff

Plan complete and saved to `docs/plans/2026-03-01-video-game-news-enrichment.md`.

**Two execution options:**

1. **Subagent-driven (this session)** – Implement task-by-task with review between tasks and fast iteration.
2. **Parallel session (separate)** – Open a new session with the executing-plans skill and run through the plan with checkpoints.

Which approach do you prefer?
