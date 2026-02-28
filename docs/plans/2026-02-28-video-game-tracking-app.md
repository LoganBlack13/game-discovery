# Video Game Tracking App Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the full video game tracking application per the functional spec: add Game, News, and TrackedGame models and migrations; external game data integration; game discovery (search modal, browse sections), game detail page with countdown and news; tracking dashboard with sort/filter; and responsive UI using existing Livewire + Flux + Tailwind stack.

**Architecture:** Add domain models (Game, News, TrackedGame) and a ReleaseStatus enum; abstract external data behind `GameDataProvider` with one implementation (e.g. RAWG). Home shows real upcoming/popular/recently released games; search is a Livewire modal (flux:modal); game detail and dashboard are Livewire or controller+Blade. Track/untrack via policy; countdown is client-side JS from server-provided release date.

**Tech Stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest. No new dependencies.

---

## Task 1: ReleaseStatus enum

**Files:**
- Create: `app/Enums/ReleaseStatus.php`

**Step 1: Create enum**

```bash
php artisan make:enum ReleaseStatus --no-interaction
```

**Step 2: Define cases**

In `app/Enums/ReleaseStatus.php`, add backed enum with string values:

- `Announced`
- `ComingSoon`
- `Released`
- `Delayed`

Follow project enum convention (TitleCase keys). Use `enum ReleaseStatus: string` with matching case values (e.g. `case Announced = 'announced';`).

**Step 3: Commit**

```bash
git add app/Enums/ReleaseStatus.php
git commit -m "feat: add ReleaseStatus enum"
```

---

## Task 2: Games table migration

**Files:**
- Create: `database/migrations/xxxx_create_games_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_games_table --no-interaction
```

**Step 2: Define schema**

Columns: `id` (bigIncrements), `title` (string), `slug` (string, unique), `description` (text, nullable), `cover_image` (string, nullable), `developer` (string, nullable), `publisher` (string, nullable), `genres` (json), `platforms` (json), `release_date` (date, nullable), `release_status` (string), `created_at`, `updated_at`. Add `external_id` (string, nullable) and `external_source` (string, nullable) for provider sync.

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

Expected: Migration runs successfully.

**Step 4: Commit**

```bash
git add database/migrations/*_create_games_table.php
git commit -m "feat: add games table migration"
```

---

## Task 3: News table migration

**Files:**
- Create: `database/migrations/xxxx_create_news_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_news_table --no-interaction
```

**Step 2: Define schema**

Columns: `id`, `game_id` (foreignId to games, cascadeOnDelete), `title` (string), `thumbnail` (string, nullable), `source` (string, nullable), `url` (string), `published_at` (timestamp, nullable), `created_at`, `updated_at`.

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 4: Commit**

```bash
git add database/migrations/*_create_news_table.php
git commit -m "feat: add news table migration"
```

---

## Task 4: Tracked games table migration

**Files:**
- Create: `database/migrations/xxxx_create_tracked_games_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_tracked_games_table --no-interaction
```

**Step 2: Define schema**

Columns: `id`, `user_id` (foreignUuid to users, cascadeOnDelete), `game_id` (foreignId to games, cascadeOnDelete), `created_at`, `updated_at`. Unique index on `['user_id', 'game_id']`.

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 4: Commit**

```bash
git add database/migrations/*_create_tracked_games_table.php
git commit -m "feat: add tracked_games table migration"
```

---

## Task 5: Game model

**Files:**
- Create: `app/Models/Game.php`
- Modify: (none for this task)

**Step 1: Write failing test**

In `tests/Unit/Models/GameTest.php` (create file): test that `Game::factory()->create(['title' => 'Foo'])` has slug derived from title (e.g. `foo`), and test scopes `upcoming()` / `released()` return correct query (e.g. release_date in future vs past). Use RefreshDatabase.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Unit/Models/GameTest.php
```

Expected: FAIL (class or factory not found).

**Step 3: Create model and factory**

```bash
php artisan make:model Game -f --no-interaction
```

- Add fillable or guarded; add `casts()`: `release_date` => `date`, `release_status` => ReleaseStatus::class, `genres` => `array`, `platforms` => `array`.
- Add boot or saving observer to set `slug` from `Str::slug($this->title)` when title is set and slug empty.
- Add scopes: `upcoming()` (release_date > now() or release_status in [Announced, ComingSoon]), `released()` (release_date <= now() or release_status Released), `byReleaseDate()` (orderBy('release_date')).

**Step 4: GameFactory**

In `database/factories/GameFactory.php`: define title, slug (from title), optional description, cover_image, developer, publisher, genres (array of a few faker words), platforms (array), release_date (optional), release_status (random enum). Match schema.

**Step 5: Run test to verify it passes**

```bash
php artisan test --compact tests/Unit/Models/GameTest.php
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Models/Game.php database/factories/GameFactory.php tests/Unit/Models/GameTest.php
git commit -m "feat: add Game model, factory, and unit tests"
```

---

## Task 6: News and TrackedGame models

**Files:**
- Create: `app/Models/News.php`, `app/Models/TrackedGame.php`
- Create: `database/factories/NewsFactory.php`, `database/factories/TrackedGameFactory.php`
- Modify: `app/Models/User.php` (add relationships)

**Step 1: Create models and factories**

```bash
php artisan make:model News -f --no-interaction
php artisan make:model TrackedGame -f --no-interaction
```

**Step 2: Implement News**

News: belongsTo Game. Fillable and casts: `published_at` => `datetime`. Factory: game_id (Game::factory), title, url, source, thumbnail, published_at.

**Step 3: Implement TrackedGame**

TrackedGame: belongsTo User, belongsTo Game. Unique constraint already in migration. Factory: user_id (User::factory), game_id (Game::factory).

**Step 4: User relationships**

In `app/Models/User.php`: `hasMany(TrackedGame::class)` and `belongsToMany(Game::class, 'tracked_games')` with timestamps. Add return type hints.

**Step 5: Game relationships**

In `app/Models/Game.php`: `hasMany(News::class)` and `belongsToMany(User::class, 'tracked_games')` (or hasMany TrackedGame). Add accessor/count for “tracked by users” for popular sort.

**Step 6: Run migrations and basic test**

Create a minimal feature test that creates User, Game, TrackedGame and asserts user->trackedGames->contains(game). Run:

```bash
php artisan test --compact --filter=TrackedGame
```

**Step 7: Commit**

```bash
git add app/Models/News.php app/Models/TrackedGame.php app/Models/Game.php app/Models/User.php database/factories/NewsFactory.php database/factories/TrackedGameFactory.php tests/Feature/...
git commit -m "feat: add News and TrackedGame models and User/Game relationships"
```

---

## Task 7: GameDataProvider contract and config

**Files:**
- Create: `app/Contracts/GameDataProvider.php`
- Modify: `config/services.php`

**Step 1: Define interface**

In `app/Contracts/GameDataProvider.php`: methods e.g. `search(string $query, ?string $platform = null, ?string $releaseStatus = null): array`, `getGameDetails(string $externalId): array`. Return type array (or DTOs if preferred).

**Step 2: Add config**

In `config/services.php`: add `'rawg' => ['key' => env('RAWG_API_KEY')]` (or equivalent). Do not use `env()` outside config.

**Step 3: Commit**

```bash
git add app/Contracts/GameDataProvider.php config/services.php
git commit -m "feat: add GameDataProvider contract and RAWG config"
```

---

## Task 8: RAWG provider implementation and SyncGameJob (optional stub)

**Files:**
- Create: `app/Services/RawgGameDataProvider.php`
- Create: `app/Jobs/SyncGameJob.php` (optional for initial scope)

**Step 1: Implement RawgGameDataProvider**

Class implements `GameDataProvider`. Use `config('services.rawg.key')`. Implement `search()` and `getGameDetails()` using HTTP client; map response to array shape that includes title, slug, description, cover_image, developer, publisher, genres, platforms, release_date, release_status, external_id. Handle errors and null key.

**Step 2: SyncGameJob (minimal)**

Job accepts external_id (and optionally game_id). Call provider `getGameDetails()`, then upsert `Game` by external_id/external_source. Use `Game::updateOrCreate(['external_source' => 'rawg', 'external_id' => $id], $attributes)`. Dispatch from artisan command or later from search.

**Step 3: Register binding (optional)**

In `AppServiceProvider` or dedicated provider: bind `GameDataProvider` to `RawgGameDataProvider` when config key present.

**Step 4: Commit**

```bash
git add app/Services/RawgGameDataProvider.php app/Jobs/SyncGameJob.php app/Providers/AppServiceProvider.php
git commit -m "feat: add RAWG provider and SyncGameJob"
```

---

## Task 9: Game detail route and view (guest view)

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/GameController.php` or Livewire `app/Livewire/Pages/GameShow.php`
- Create: `resources/views/games/show.blade.php` or Livewire view

**Step 1: Write failing feature test**

In `tests/Feature/GameShowTest.php`: `get(route('games.show', Game::factory()->create(['slug' => 'my-game'])))->assertOk()->assertSee('my-game');` and 404 for invalid slug.

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/GameShowTest.php
```

Expected: FAIL (route or view missing).

**Step 3: Add route and controller/component**

In `routes/web.php`: `Route::get('/games/{game:slug}', ...)->name('games.show');` (use route model binding; ensure binding uses `slug` key).

In `GameController` or Livewire: resolve game, return view with game. View: title, cover, description, developer, publisher, genres, platforms, release_date, release_status.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/GameShowTest.php
```

**Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/GameController.php resources/views/games/show.blade.php tests/Feature/GameShowTest.php
git commit -m "feat: add game detail page (guest view)"
```

---

## Task 10: Game detail – countdown and news

**Files:**
- Modify: `resources/views/games/show.blade.php` (or Livewire view)

**Step 1: Add news section**

In game view: query or pass `$game->news()->orderByDesc('published_at')->get()`. Display title, thumbnail, source, published_at, link (url). Newest first.

**Step 2: Add countdown block**

When `release_date` is in the future: output release date as ISO for JS; render placeholder (e.g. “Days / Hours / Minutes”). Add small script (Alpine or vanilla) that computes countdown from server-provided date and updates every minute; when date passed, show “Released”.

**Step 3: Commit**

```bash
git add resources/views/games/show.blade.php
git commit -m "feat: game detail countdown and news section"
```

---

## Task 11: Track/untrack policy and action

**Files:**
- Create: `app/Policies/GamePolicy.php`
- Modify: Game controller or Livewire (add track/untrack)

**Step 1: Write failing test**

Guest cannot track; auth user can add and remove. Test: post to track endpoint as guest -> 401/302; as user -> 200 and tracked_games count increases; then untrack -> count decreases.

**Step 2: Create policy**

```bash
php artisan make:policy GamePolicy --model=Game --no-interaction
```

Implement `update` or custom `track` / `untrack`: only authenticated user can track/untrack (or use `view` for viewing, custom method for track). Register in `AuthServiceProvider` or policy discovery.

**Step 3: Add track/untrack endpoint or Livewire method**

e.g. `POST /games/{game}/track` and `DELETE /games/{game}/track` (or Livewire `toggleTrack()`). Controller/Livewire: authorize, then attach/detach `auth()->user()->trackedGames()`. Return JSON or redirect with flash for “instant update”.

**Step 4: Run test**

```bash
php artisan test --compact --filter=Track
```

**Step 5: Commit**

```bash
git add app/Policies/GamePolicy.php routes/web.php app/Http/Controllers/... tests/Feature/TrackingTest.php
git commit -m "feat: track/untrack game with policy"
```

---

## Task 12: Game detail – tracking button

**Files:**
- Modify: `resources/views/games/show.blade.php` (or Livewire)

**Step 1: Add button**

If guest: show “Log in to track” or disabled. If auth: show “Track Game” or “Remove from Tracking” based on `auth()->user()->trackedGames()->where('game_id', $game->id)->exists()`. Use Livewire component for toggle (wire:click to track/untrack, then refresh state) or form POST + redirect back with flash.

**Step 2: Commit**

```bash
git add resources/views/games/show.blade.php app/Livewire/...
git commit -m "feat: tracking button on game detail page"
```

---

## Task 13: Home page – real data (upcoming, popular, recently released)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php` and its Livewire class

**Step 1: Write failing test**

In `tests/Feature/WelcomePageTest.php` or `HomeTest.php`: create games (upcoming, released), visit `/`, assert see game titles and sections (upcoming, popular, recently released).

**Step 2: Run test to verify it fails**

**Step 3: Replace placeholder data**

In welcome Livewire: inject or query `Game::query()->upcoming()->byReleaseDate()->limit(12)`, `Game::query()->withCount('trackedByUsers')->orderByDesc('tracked_by_users_count')->limit(12)`, `Game::query()->released()->orderByDesc('release_date')->limit(12)`. Pass to view. Update Blade to loop over real games (cover, title, release date, countdown, platforms). Keep existing layout/Flux/Tailwind.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/WelcomePageTest.php
```

**Step 5: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php app/Livewire/... tests/Feature/HomeTest.php
git commit -m "feat: home page uses real upcoming/popular/recently released games"
```

---

## Task 14: Search modal (Livewire + flux:modal)

**Files:**
- Create: `app/Livewire/GameSearchModal.php` and view
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Create Livewire component**

```bash
php artisan make:livewire GameSearchModal --no-interaction
```

Property `$query`; `wire:model.live.debounce.300ms="query"` on input. Computed or method `getResults()`: `Game::query()->when($query, fn ($q) => $q->where('title', 'ilike', '%'.$query.'%'))->limit(10)`. Return games with cover, title, release_date, platforms.

**Step 2: Add modal to layout**

In `layouts/app.blade.php`: add trigger (button or kbd ⌘K) that opens flux:modal. Modal content: Livewire `GameSearchModal` with input and list of results; each result links to `route('games.show', $game)` and shows tracking button (guest vs auth).

**Step 3: Commit**

```bash
git add app/Livewire/GameSearchModal.php resources/views/livewire/game-search-modal.blade.php resources/views/layouts/app.blade.php
git commit -m "feat: global game search spotlight modal"
```

---

## Task 15: Dashboard route and page

**Files:**
- Modify: `routes/web.php`
- Create: `app/Livewire/Pages/Dashboard.php` (or controller + view)
- Create: `resources/views/dashboard.blade.php` or Livewire view

**Step 1: Write failing test**

Authenticated user visits `/dashboard` -> 200 and sees only their tracked games. Guest -> redirect to login.

**Step 2: Add route**

`Route::get('/dashboard', ...)->name('dashboard')->middleware(['auth', 'verified']);`

**Step 3: Implement dashboard**

List `auth()->user()->trackedGames()->with('game')` (and optional latest news per game). Display: cover, title, release_date, countdown, platforms, last news date. Use Flux cards; responsive grid.

**Step 4: Add Dashboard link in header**

In `layouts/app.blade.php`: for @auth, add link to `route('dashboard')`.

**Step 5: Run test**

```bash
php artisan test --compact --filter=Dashboard
```

**Step 6: Commit**

```bash
git add routes/web.php app/Livewire/Pages/Dashboard.php resources/views/... resources/views/layouts/app.blade.php tests/Feature/DashboardTest.php
git commit -m "feat: tracking dashboard for authenticated users"
```

---

## Task 16: Dashboard sort and filter

**Files:**
- Modify: `app/Livewire/Pages/Dashboard.php` and view

**Step 1: Add sort options**

Livewire properties: `$sort = 'release_date'` (or recently_added, alphabetical, countdown). In query, apply orderBy/orderByDesc based on sort. Dropdown in view with wire:model.

**Step 2: Add filters**

Filter by: Released / Upcoming / No release date; optional platform. Apply scopes or where to the tracked games query.

**Step 3: Commit**

```bash
git add app/Livewire/Pages/Dashboard.php resources/views/...
git commit -m "feat: dashboard sort and filter"
```

---

## Task 17: Responsive and accessibility pass

**Files:**
- Modify: `resources/views/games/show.blade.php`, dashboard view, welcome, layout

**Step 1: Breakpoints**

Ensure game cards and dashboard grid use Tailwind sm/md/lg (320–768, 768–1024, 1024+). Use gap utilities; no margins for list spacing.

**Step 2: Dark mode**

Ensure new views support dark mode (existing Flux appearance + dark: classes).

**Step 3: Labels and focus**

Add aria-labels where needed; ensure search modal and buttons are focusable and keyboard-accessible.

**Step 4: Commit**

```bash
git add resources/views/...
git commit -m "chore: responsive and a11y pass for game and dashboard"
```

---

## Task 18: Run full test suite and Pint

**Step 1: Run tests**

```bash
php artisan test --compact
```

Fix any failing tests.

**Step 2: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 3: Commit**

```bash
git add -A && git status
git commit -m "test: fix and style for video game tracking feature"
```

---

## Execution handoff

Plan complete and saved to `docs/plans/2026-02-28-video-game-tracking-app.md`.

**Two execution options:**

1. **Subagent-Driven (this session)** – Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans skill for batch execution with checkpoints.

**Which approach?**
