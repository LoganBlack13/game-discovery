# User Dashboard Improvements Implementation Plan

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** Improve the user dashboard with a visual “Up next” block (3 tracked games by nearest release, with title, picture, and countdown), a reusable visual game card component, and a new Recent Updates Feed (personalized activity for tracked games: news, release date changes, releases; filterable, newest first, pagination/infinite scroll).

**Architecture:** Dashboard layout: (1) “Up next” section — 3 tracked games ordered by nearest release date first, each rendered with a new game card component (title, cover, countdown). (2) “Recent Updates Feed” — unified feed built from News (as “New Article”) plus GameActivity records (release date changed, announced, game released, major update). GameActivity is created when sync or admin update changes release_date/release_status. Feed is filtered to user’s tracked games, sort newest first, optional filter (news only / release updates only / all), pagination or infinite scroll. One new model + migration (GameActivity), SyncGameJob and admin update flow write activities when relevant; feed query unions news and activities for tracked games.

**Tech Stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Alpine (countdown), Pest. No new dependencies.

---

## UI/UX Direction (frontend-design)

- **Tone:** Bold and visual for the dashboard: editorial/magazine meets soft depth. Differentiate from admin’s utilitarian look. Keep Syne + DM Sans and existing `--color-accent`; add a clear visual hierarchy and one memorable element (e.g. prominent countdown treatment or card hover/motion).
- **Game card (reusable):** Must show title, picture (cover), and countdown until release. Really visual: strong cover imagery (aspect ratio consistent, optional subtle overlay or gradient at bottom for title legibility). Countdown: large, readable (e.g. “X days” or “D:H:M” with a subtle pulse or color accent). Card should feel like a “coming soon” teaser — not cluttered; title + cover + countdown are the stars. Support dark mode.
- **“Up next” block:** Only 3 games, nearest release first. Horizontal or grid of 3; generous spacing (gap). Optional short section heading like “Up next” or “Coming soon”.
- **Recent Updates Feed:** Each item: game cover thumbnail, game title, update type label (e.g. “Release Date Changed”, “New Article”, “Game Released”), short description, timestamp (relative or absolute), link to game page or article URL. List or card list; newest first. Optional filter tabs or dropdown: “All”, “News only”, “Release updates only”. Auto-refresh on dashboard load; only tracked games; pagination (e.g. “Load more”) or infinite scroll.
- **Spatial composition:** Up next (3 cards) then feed below; or two columns on large screens (up next left, feed right). Avoid generic card grids; use asymmetry or a clear focal block for “Up next”.
- **Motion:** Staggered reveal on load for cards/feed items (e.g. animation-delay); subtle hover on cards and feed rows. Countdown can be static (no need for live ticker on dashboard if we refresh on focus).
- **Accessibility:** `aria-label` on “Up next” and “Recent updates” regions; feed links and buttons keyboard-focusable; relative timestamps with `datetime` where appropriate.

---

## Task 1: GameActivity model and migration

**Files:**
- Create: `app/Enums/GameActivityType.php`
- Create: `database/migrations/xxxx_create_game_activities_table.php`
- Create: `app/Models/GameActivity.php`
- Modify: `app/Models/Game.php` (add `activities()` relationship)

**Step 1: Create GameActivityType enum**

```bash
php artisan make:enum GameActivityType --no-interaction
```

Define cases: `ReleaseDateChanged`, `ReleaseDateAnnounced`, `GameReleased`, `MajorUpdate`. (News items stay in the News table; feed maps them to display type “New Article” in the view.) Use TitleCase keys per project conventions.

**Step 2: Create migration**

```bash
php artisan make:migration create_game_activities_table --no-interaction
```

Schema: `id`, `game_id` (foreignId, cascadeOnDelete), `type` (string, e.g. enum value), `title` (string), `description` (text, nullable), `url` (string, nullable), `occurred_at` (timestamp), `timestamps`. Index `game_id` and `occurred_at` for feed queries.

**Step 3: Create GameActivity model**

```bash
php artisan make:model GameActivity --no-interaction
```

Fillable: `game_id`, `type`, `title`, `description`, `url`, `occurred_at`. Cast `occurred_at` to datetime, `type` to GameActivityType enum. Relationship `game(): BelongsTo(Game)`.

**Step 4: Add Game hasMany GameActivity**

In `app/Models/Game.php`, add `activities(): HasMany(GameActivity::class)` ordered by `occurred_at` desc.

**Step 5: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 6: Commit**

```bash
git add app/Enums/GameActivityType.php database/migrations/*_create_game_activities_table.php app/Models/GameActivity.php app/Models/Game.php
git commit -m "feat: GameActivity model and migration for feed events"
```

---

## Task 2: Record GameActivity on release changes (SyncGameJob)

**Files:**
- Modify: `app/Jobs/SyncGameJob.php`
- Test: `tests/Feature/Jobs/SyncGameJobTest.php` or `tests/Unit/Jobs/SyncGameJobTest.php`

**Step 1: Write failing test**

When a game already exists and sync updates `release_date` to a new value, a `GameActivity` with type `ReleaseDateChanged` (and appropriate title/description) is created. When sync sets a game to released (e.g. release_date in past or release_status Released), a `GameActivity` type `GameReleased` is created. When a game had null release_date and sync sets a new release_date, create `ReleaseDateAnnounced`. (Adjust cases to match enum; at least one test for release date change.)

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=SyncGameJob
```

**Step 3: Implement in SyncGameJob**

In `handle()`: resolve existing game by external_source + external_id if `gameId` provided (or after updateOrCreate). After updateOrCreate, compare previous `release_date` and `release_status` (if model was existing) with new values. If release_date changed from one non-null to another → create GameActivity `ReleaseDateChanged` with title/description mentioning old and new date. If release_date was null and now set → `ReleaseDateAnnounced`. If now released (date in past or status Released) → `GameReleased`. Set `occurred_at` to now(). Do not create duplicate activities for same “event” in one sync (e.g. one activity per sync when criteria met).

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact --filter=SyncGameJob
```

**Step 5: Commit**

```bash
git add app/Jobs/SyncGameJob.php tests/Feature/Jobs/SyncGameJobTest.php
git commit -m "feat: record GameActivity on release changes in SyncGameJob"
```

---

## Task 3: Record GameActivity on admin game update (optional release events)

**Files:**
- Modify: `app/Http/Controllers/Admin/GameController.php` (or wherever game update is handled)
- Test: `tests/Feature/Admin/UpdateGameTest.php` or existing admin game test

**Step 1: Write failing test**

When admin updates a game’s release_date or release_status and the change represents “release date changed” or “game released” or “release date announced”, a corresponding `GameActivity` is created.

**Step 2: Run test to verify it fails**

**Step 3: In update handler, after updating game**

Compare old vs new `release_date` and `release_status`. Create `GameActivity` for same cases as Task 2 (ReleaseDateChanged, ReleaseDateAnnounced, GameReleased). Use same title/description conventions.

**Step 4: Run test to verify it passes**

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/GameController.php tests/Feature/Admin/UpdateGameTest.php
git commit -m "feat: record GameActivity on admin game update"
```

---

## Task 4: Dashboard “Up next” – data (3 games, nearest release first)

**Files:**
- Modify: `resources/views/components/⚡dashboard-game-list.blade.php` (Livewire component)

**Step 1: Add computed property for “up next” games**

In the same Livewire component (or a dedicated one if preferred), add a property or method that returns 3 tracked games ordered by nearest release date first: `auth()->user()->trackedGames()->whereNotNull('release_date')->where('release_date', '>', now())->orderBy('release_date')->limit(3)->get()`. If fewer than 3 upcoming, include games with release_date in the past or null only after upcoming (so “nearest first” means: next 3 by release_date asc, then fill with others if needed). Clarification: “nearest date first” = ascending release_date for future dates; show only 3. So: `orderBy('release_date')->limit(3)` for upcoming; if we want “nearest first” globally we can do `orderByRaw('CASE WHEN release_date > ? THEN 0 ELSE 1 END')->orderBy('release_date')` to put upcoming first then by date. Keep it simple: upcoming only, 3 games, `orderBy('release_date')->limit(3)`.

**Step 2: Expose in view**

Pass `upNext` (or equivalent) to the blade view for the “Up next” block (used in next task).

**Step 3: Commit**

```bash
git add resources/views/components/⚡dashboard-game-list.blade.php
git commit -m "feat: dashboard up next query (3 games, nearest release first)"
```

---

## Task 5: Game card Blade component (title, picture, countdown)

**Files:**
- Create: `resources/views/components/game-card.blade.php` (or `resources/views/components/dashboard/game-card.blade.php`)
- Use existing countdown pattern from `resources/views/games/show.blade.php` (data-release-iso, data-countdown, data-countdown-display)

**Step 1: Create component**

Component accepts `$game` (required). Render: cover image (or placeholder with first letter), game title, and countdown until release (only when `$game->release_date` is in the future). Use same countdown markup/attributes as game show page so existing Alpine or script can drive it, or reuse a small Alpine snippet for “X days” / “D:H:M”. Ensure title, picture, and countdown are clearly visible; card is link to `route('games.show', $game)`.

**Step 2: Apply frontend-design**

Make it visually strong: clear aspect ratio for cover (e.g. aspect-[3/4]), optional gradient overlay at bottom for title readability; countdown with accent color and readable typography (e.g. font-mono, tabular-nums). Support dark mode (dark: classes). Avoid clutter; no platforms/genres on this card unless design calls for one line.

**Step 3: Use in dashboard “Up next”**

In `dashboard-game-list` view, add “Up next” section: loop `$this->upNext` and render the new game card component for each. Only 3 items.

**Step 4: Commit**

```bash
git add resources/views/components/game-card.blade.php resources/views/components/⚡dashboard-game-list.blade.php
git commit -m "feat: game card component with title, picture, countdown; use in Up next"
```

---

## Task 6: Dashboard layout – Up next + main list

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/components/⚡dashboard-game-list.blade.php`

**Step 1: Layout structure**

Dashboard: keep “My tracked games” heading. Above the full game list, add a distinct “Up next” section (heading + 3 game cards in a row/grid). Below that, keep existing sort/filter and full tracked games grid. Ensure “Up next” only shows when there are upcoming tracked games; otherwise hide section or show empty state.

**Step 2: Limit main list to full set; Up next is separate**

Confirm main list is still all tracked games (with sort/filter); “Up next” is a separate fixed block of 3 (nearest release). No duplicate logic; main list can still be sorted by countdown so “soonest first” is an option there too.

**Step 3: Commit**

```bash
git add resources/views/dashboard.blade.php resources/views/components/⚡dashboard-game-list.blade.php
git commit -m "feat: dashboard layout with Up next section and main list"
```

---

## Task 7: Recent Updates Feed – backend (unified feed query)

**Files:**
- Create: `app/Services/DashboardFeedService.php` (or similar)
- Or implement in Livewire component that will render the feed

**Step 1: Define feed item structure**

Feed items (for view): game_id, game (cover, title, slug), type (NewArticle | ReleaseDateChanged | ReleaseDateAnnounced | GameReleased | MajorUpdate), title, description, url (game page or article url), occurred_at. News items: type NewArticle, title = news title, url = news url, occurred_at = published_at. GameActivity items: type from enum, title/description/url from activity, occurred_at.

**Step 2: Implement feed query**

For authenticated user, get tracked game ids. Fetch: (1) News for those games (select id, game_id, title, url, published_at as occurred_at); (2) GameActivity for those games. Merge into one collection (or use a DTO/array shape), sort by occurred_at desc. Support filter: “news” (only News), “release” (only GameActivity types that are release-related), “all” (default). Support pagination: limit + offset or cursor. Return array/collection of feed item structures.

**Step 3: Add optional factory/seeder**

If useful, add a GameActivity factory and seed one or two activities for tests. Not required for minimal implementation.

**Step 4: Commit**

```bash
git add app/Services/DashboardFeedService.php
git commit -m "feat: dashboard feed service (unified news + activities, filter, sort)"
```

---

## Task 8: Recent Updates Feed – Livewire component and view

**Files:**
- Create: `resources/views/components/⚡dashboard-feed.blade.php` (Livewire)
- Modify: `resources/views/dashboard.blade.php`

**Step 1: Livewire component**

Properties: `filter = 'all'` (all | news | release), `perPage = 15`. Use DashboardFeedService (or inline query) to load first page of feed items. Expose `feedItems` and `hasMorePages` (or equivalent for “Load more”).

**Step 2: Feed view**

Section “Recent updates”. Per item: game cover thumbnail, game title, update type label (e.g. “New Article”, “Release Date Changed”), short description, timestamp (e.g. relative), link (to game page or item url). List layout; newest first. Optional filter tabs or select: All / News only / Release updates only (wire:model.live filter, re-fetch first page).

**Step 3: Pagination or infinite scroll**

Either “Load more” button (wire:click load more, append items) or infinite scroll (wire:scroll or similar). Document choice in plan; recommend “Load more” for simplicity and a11y.

**Step 4: Mount and refresh**

On dashboard load, component mounts and loads feed (auto-refresh on load). Only tracked games (already enforced by feed query).

**Step 5: Add to dashboard**

In `resources/views/dashboard.blade.php`, add the feed section (e.g. below “Up next” and above or beside the main game list). Two-column on large screens optional: left “Up next” + right “Recent updates”, with “My tracked games” full list below; or single column: Up next → Recent updates → My tracked games.

**Step 6: Commit**

```bash
git add resources/views/components/⚡dashboard-feed.blade.php resources/views/dashboard.blade.php
git commit -m "feat: Recent Updates Feed Livewire component and dashboard integration"
```

---

## Task 9: Feed styling and accessibility (frontend-design)

**Files:**
- Modify: `resources/views/components/⚡dashboard-feed.blade.php`

**Step 1: Visual design**

Apply cohesive styling: feed item cards or rows with clear separation; game thumbnail size consistent; update type as a small label/badge (e.g. “New Article” in one color, “Release Date Changed” in another). Typography: use font-display for game title, clear hierarchy. Staggered reveal on load (animation-delay per item) if desired. Dark mode.

**Step 2: Accessibility**

`aria-label` for “Recent updates” region. Links have descriptive text or aria-label (e.g. “Read article: …” or “View game”). Timestamps use `<time datetime="...">`. Focus visible on filter and “Load more”.

**Step 3: Commit**

```bash
git add resources/views/components/⚡dashboard-feed.blade.php
git commit -m "feat: feed styling and accessibility"
```

---

## Task 10: Tests for dashboard and feed

**Files:**
- Modify: `tests/Feature/DashboardTest.php`
- Create or extend: tests for feed (guest no feed, auth sees only tracked games’ updates, filter works)

**Step 1: Dashboard tests**

Authenticated user sees “Up next” when they have tracked games with future release dates (at most 3). Authenticated user sees Recent Updates section; feed contains only items for their tracked games.

**Step 2: Feed filter test**

As authenticated user, change filter to “News only”; feed shows only news items. “Release updates only” shows only GameActivity items.

**Step 3: Run tests**

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

**Step 4: Commit**

```bash
git add tests/Feature/DashboardTest.php
git commit -m "test: dashboard up next and feed scope and filter"
```

---

## Task 11: Pint and final pass

**Files:**
- All touched PHP files

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Run full dashboard and feed tests**

```bash
php artisan test --compact --filter=Dashboard
```

**Step 3: Commit**

```bash
git add -A && git status
git commit -m "style: pint for user dashboard changes" (if any changes)
```

---

## Summary

| Task | Description |
|------|-------------|
| 1 | GameActivity model + migration + enum |
| 2 | SyncGameJob creates GameActivity on release changes |
| 3 | Admin game update creates GameActivity when relevant |
| 4 | Dashboard “Up next” query (3 games, nearest release first) |
| 5 | Game card component (title, picture, countdown); use in Up next |
| 6 | Dashboard layout: Up next + main list |
| 7 | Dashboard feed service (unified news + activities, filter, sort) |
| 8 | Recent Updates Feed Livewire + view + dashboard integration |
| 9 | Feed styling and accessibility |
| 10 | Tests for dashboard and feed |
| 11 | Pint and final test pass |

---

## Notes

- **Game card:** Reusable for dashboard “Up next” and potentially elsewhere (e.g. welcome page). Keep props minimal ($game).
- **Feed content types:** “Major update” (e.g. beta announced, early access) can be created manually or via a future job; for initial release, recording ReleaseDateChanged, ReleaseDateAnnounced, and GameReleased in sync/admin is enough. News is already in DB; no need to duplicate into GameActivity.
- **Optional:** If “infinite scroll” is chosen, use Livewire’s wire:scroll or a small Alpine hook to load next page when user scrolls near bottom; otherwise “Load more” is sufficient and easier to test.
