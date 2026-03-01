# Improve Add Game to Database

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** Improve the admin “Add game” flow by (1) showing **up to 10 results per source** (RAWG and IGDB) in **two columns** so admins can easily choose which game to import from which source, (2) allowing **existing games to be updated with fresh data** from their source, and (3) recording **last sync metadata** and **only persisting updates when something changed** since the last sync.

**Architecture:** Keep both sources (RAWG, IGDB); cap search at 10 results per source (providers already use limit 10). Restructure the add-game Livewire component to fetch and store results **per source** and render a two-column layout (e.g. RAWG left, IGDB right). Add `last_synced_at` (nullable timestamp) to `games`; in `SyncGameJob`, after fetching details, compare with the existing game’s attributes and only run `updateOrCreate` (and set `last_synced_at`) when at least one synced field changed. For games already in the database, show an “Update” action that re-runs sync to refresh data; the job’s compare logic avoids unnecessary writes when nothing changed.

**Tech stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest. No new dependencies.

**Key files:**
- [app/Models/Game.php](app/Models/Game.php) – add `last_synced_at` and cast
- [app/Jobs/SyncGameJob.php](app/Jobs/SyncGameJob.php) – compare details with existing, conditional update, set `last_synced_at`
- [resources/views/components/⚡admin-rawg-add-game.blade.php](resources/views/components/⚡admin-rawg-add-game.blade.php) – results per source, two-column UI, “Update” for existing games
- [app/Services/RawgGameDataProvider.php](app/Services/RawgGameDataProvider.php), [app/Services/IgdbGameDataProvider.php](app/Services/IgdbGameDataProvider.php) – already limit 10; document or enforce in contract if desired

---

## Out of scope / assumptions

- No merging of two sources into one game record; each game has a single `external_source` + `external_id`. Re-sync always uses that same source.
- “Update” means re-fetch from the same provider and overwrite local fields only when changed; no conflict resolution between sources.
- Two-column layout is the main UX change; responsive behavior: stack columns on small screens (e.g. RAWG then IGDB).

---

## Task 1: Migration – `last_synced_at` on games

**Files:**
- Create: `database/migrations/xxxx_add_last_synced_at_to_games_table.php`

**Step 1: Create migration**

Use `php artisan make:migration add_last_synced_at_to_games_table --table=games --no-interaction`.

In `up()`: add nullable timestamp column `last_synced_at` (e.g. `$table->timestamp('last_synced_at')->nullable();`). In `down()`: drop the column.

**Step 2: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 3: Update Game model**

In [app/Models/Game.php](app/Models/Game.php): add `last_synced_at` to `$fillable`; in `casts()` add `'last_synced_at' => 'datetime'`. Add `@property \Carbon\CarbonInterface|null $last_synced_at` to the model’s PHPDoc.

**Step 4: Commit**

```bash
git add database/migrations/ app/Models/Game.php
git commit -m "feat: add last_synced_at to games for sync tracking"
```

---

## Task 2: SyncGameJob – compare and update only when changed

**Files:**
- Modify: [app/Jobs/SyncGameJob.php](app/Jobs/SyncGameJob.php)

**Step 1: Define comparable payload**

After `getGameDetails()`, build a normalized representation of the synced fields (title, slug, description, cover_image, developer, publisher, genres, platforms, release_date, release_status) in a form comparable to the existing model (e.g. same types: date as Y-m-d string or Carbon, arrays sorted for genres/platforms so comparison is stable).

**Step 2: Compare with existing game**

If an existing game is found (same `external_source` + `external_id`), compare each synced attribute (or a single hash of the payload) with the existing model. Normalize existing values for comparison (e.g. `release_date` to same string format, arrays to sorted arrays). If all match, do **not** call `updateOrCreate` with the full payload; optionally skip the write entirely, or only update `last_synced_at` in a minimal update—recommendation: skip write so “last synced” reflects “last time we actually wrote.” If any field differs, proceed to update.

**Step 3: Update or create with last_synced_at**

When creating or updating the game (because new or because something changed), set `last_synced_at` to `now()` in the payload passed to `updateOrCreate`. Keep existing behavior: `GameActivityRecorder::recordReleaseChanges()` only when there was an actual update (existing game and release_date or release_status changed).

**Step 4: Tests**

- Unit or feature test: when syncing an existing game with identical details, assert no change to `updated_at` (or that `last_synced_at` is not updated if you skip write), and that no new activity is recorded for “release change” when nothing changed.
- When syncing with one field different (e.g. title), assert game is updated and `last_synced_at` is set.
- When creating a new game, assert `last_synced_at` is set.

**Step 5: Commit**

```bash
git add app/Jobs/SyncGameJob.php tests/
git commit -m "feat: sync game only when data changed; set last_synced_at"
```

---

## Task 3: Add-game UI – 10 per source, two columns

**Files:**
- Modify: [resources/views/components/⚡admin-rawg-add-game.blade.php](resources/views/components/⚡admin-rawg-add-game.blade.php)

**Step 1: Results per source**

Change the component so search results are **not** a single merged list. Instead:

- Define a computed property or method that returns a structure keyed by source, e.g. `['rawg' => [...], 'igdb' => [...]]`. For each source, call `$resolver->resolve($source)->search($query)` and take at most 10 items (slice if a provider ever returns more). Keep the same result item shape (title, slug, cover_image, external_id, external_source, etc.).

**Step 2: Already-in-DB and “Update” action**

For each source’s list, compute “already in database” the same way as today (e.g. `Game::where('external_source', $source)->whereIn('external_id', $ids)->pluck('external_id')`). In the template, for each result:

- If not in DB: show “Add to database” button (current behavior).
- If in DB: show “Already in database” and an “Update” button that calls the same sync action (e.g. `addGame($externalId, $externalSource)`). “Update” re-runs `SyncGameJob`; the job will compare and only write if something changed. Optionally show `last_synced_at` for that game (e.g. “Last synced: 2 hours ago”) if the game is in the list and you have the model loaded.

**Step 3: Two-column layout**

- Desktop: two columns side by side (e.g. `grid grid-cols-1 lg:grid-cols-2 gap-6` or `flex gap-6`). Left column: “RAWG” heading + list of up to 10 RAWG results. Right column: “IGDB” heading + list of up to 10 IGDB results. Use consistent card/list styling per result (cover, title, release date, platforms/genres, Add or Update button).
- Mobile: stack columns (RAWG first, then IGDB) so one column per row.
- Keep one shared search input above the two columns; same debounce and loading state. Loading applies to both columns (e.g. “Searching…” in each column or a single message above).

**Step 4: Accessibility and copy**

- Column headings: “RAWG” and “IGDB” with `aria-label` or headings (e.g. `h2`). Results lists: `aria-label="RAWG results"` and `aria-label="IGDB results"`.
- Empty state per column: e.g. “No RAWG results” / “No IGDB results” when that source returns no results; keep “Enter a game name to search…” when query is empty.

**Step 5: Commit**

```bash
git add resources/views/components/⚡admin-rawg-add-game.blade.php
git commit -m "feat: add-game two-column layout, 10 per source, Update for existing"
```

---

## Task 4: Enforce 10 per source in providers (optional)

**Files:**
- [app/Services/RawgGameDataProvider.php](app/Services/RawgGameDataProvider.php)
- [app/Services/IgdbGameDataProvider.php](app/Services/IgdbGameDataProvider.php)

**Step 1: Verify and document**

RAWG already uses `page_size => 10`; IGDB uses `limit 10` in the body. Add a class constant e.g. `private const int SEARCH_LIMIT = 10` in each and use it so the limit is explicit and consistent. Optionally add a short PHPDoc or comment that add-game UI expects at most 10 results per source. No change to the contract required if both already return ≤10.

**Step 2: Slice in Livewire (safety)**

In the Livewire component, when building results per source, `array_slice($results, 0, 10)` so the UI never shows more than 10 per column even if a provider behavior changes.

**Step 3: Commit**

```bash
git add app/Services/RawgGameDataProvider.php app/Services/IgdbGameDataProvider.php resources/views/components/⚡admin-rawg-add-game.blade.php
git commit -m "chore: enforce 10 results per source in add-game"
```

---

## Task 5: Feature tests

**Files:**
- Modify: [tests/Feature/AdminAddGameTest.php](tests/Feature/AdminAddGameTest.php)

**Step 1: Two-column and per-source results**

- As admin, visit add-game page, type a query; assert response contains both “RAWG” and “IGDB” (column headings or labels) and that results are present in two distinct sections (e.g. two lists). Mock or fake providers to return known results per source and assert each column shows up to 10 items.

**Step 2: Update existing game**

- Create a game in DB (e.g. external_source rawg, external_id 123). Mock the same provider to return the same details; call `addGame('123', 'rawg')` (simulating “Update”). Assert game’s `updated_at` or `last_synced_at` behavior (no change if nothing changed, or only `last_synced_at` if you touch it). Then mock provider to return different title; call `addGame('123', 'rawg')` again; assert game title updated and `last_synced_at` set.

**Step 3: Already in DB and Update button**

- With a game already in DB, ensure the component shows “Already in database” and an “Update” action; firing Update runs sync and (with mocks) updates or skips as expected.

**Step 4: Run tests**

```bash
php artisan test --compact tests/Feature/AdminAddGameTest.php
```

**Step 5: Commit**

```bash
git add tests/Feature/AdminAddGameTest.php
git commit -m "test: add-game two columns, update existing, last_synced_at"
```

---

## Task 6: Full test suite and Pint

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
git commit -m "chore: style and fix tests for add-game improvements"
```

---

## Execution handoff

Plan saved to `docs/plans/2026-03-01-improve-add-game-database.md`.

**Summary:** (1) Add `last_synced_at` to games and set it in `SyncGameJob` when a write occurs. (2) In `SyncGameJob`, compare fetched details with existing game and only run `updateOrCreate` when something changed. (3) Add-game UI: results per source (max 10 each), two-column layout (RAWG | IGDB), “Add to database” for new games and “Update” for existing ones; Update re-runs sync and the job avoids redundant writes when data is unchanged.

**Next steps:** Execute task-by-task (e.g. with executing-plans skill) or implement manually in the order above.
