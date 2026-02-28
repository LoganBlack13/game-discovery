# Admin: Add Game from RAWG Implementation Plan

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** Let admins search games on RAWG (API) and add a selected game to the app database. Delivered as a Livewire component with live search results and clear add-to-database progress feedback.

**Architecture:** Admin-only section on the existing admin dashboard. A single Livewire component (anonymous or class) searches via `GameDataProvider::search()` with debounced input, displays RAWG results, and adds a game by calling existing `SyncGameJob` (sync dispatch for immediate feedback) or by invoking the same sync logic in-process so the UI can show “Adding…” then “Added” or error. Reuse `RawgGameDataProvider`, `SyncGameJob`, and `Game` model; no new backend services. UI follows frontend-design principles: clear hierarchy, distinct states (idle / searching / results / adding / success|error), and restrained motion.

**Tech Stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest. No new dependencies.

---

## UI/UX Direction (frontend-design)

- **Purpose:** Admin tool to discover RAWG games and one-click add to DB. Primary user: admin; secondary need: clarity and speed.
- **Tone:** Utilitarian / industrial. Admin area is functional first: strong labels, clear states, no decorative clutter. Align with existing admin layout (Syne + DM Sans, zinc palette, light/dark).
- **Key UX:**
  - **Search:** Single search field; results update live as user types (debounced 1000 ms). Empty state: “Enter a game name to search RAWG.” Loading: inline spinner or skeleton for result list. No results: “No RAWG results. Try another query.”
  - **Results:** List of RAWG hits (cover thumbnail, title, release date, platforms/genres if available). Each row has one primary action: “Add to database.” Show if already in DB (e.g. “Already in database” badge or disabled button) by checking `Game::where('external_source', 'rawg')->where('external_id', $id)->exists()`.
  - **Progress:** On “Add to database”: button shows loading state (wire:loading); on success show short success message (e.g. toast or inline “Added: {title}”) and update that row to “Already in database”; on failure show error message (e.g. “Could not add game. Try again.”).
- **Motion:** Subtle only—e.g. result list stagger on first load (animation-delay per item), button loading spinner. No heavy animations.
- **Accessibility:** `aria-label` on search, `aria-busy` / `aria-live` for loading and status messages, focus management if opening in modal (optional).

---

## Task 1: Admin route and view for “Add game”

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/admin/add-game.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`

**Step 1: Add route**

In `routes/web.php`, inside the existing `prefix('admin')->name('admin.')->middleware('admin')` group, add:

```php
Route::get('/add-game', fn () => view('admin.add-game'))->name('add-game');
```

So full path is `/admin/add-game`, name `admin.add-game`.

**Step 2: Create admin add-game view**

Create `resources/views/admin/add-game.blade.php` using the admin layout. Content: page title “Add game from RAWG” and a short description (e.g. “Search RAWG and add a game to the database.”). Include the Livewire component (to be created in Task 2): `<livewire:admin-rawg-add-game />`.

Use `<x-layouts.admin title="Add game from RAWG">` and slot content with heading + description + Livewire component.

**Step 3: Link from admin dashboard**

In `resources/views/admin/dashboard.blade.php`, add a link/card to “Add game from RAWG” pointing to `route('admin.add-game')` so admins can reach the new page from the dashboard.

**Step 4: Commit**

```bash
git add routes/web.php resources/views/admin/add-game.blade.php resources/views/admin/dashboard.blade.php
git commit -m "feat: admin add-game route and view"
```

---

## Task 2: Livewire component – search and results (no add yet)

**Files:**
- Create: `resources/views/components/⚡admin-rawg-add-game.blade.php`

**Step 1: Create Livewire component**

Create an anonymous Livewire component (same pattern as `⚡game-search-modal.blade.php`). In the PHP block: inject `GameDataProvider` (or use `app(GameDataProvider::class)`). Public property: `$query = ''`. Use a computed property or method that returns search results: when `trim($query)` is non-empty, call `$provider->search(trim($query))` and return the array; otherwise return `[]`. Do not add “Add to database” logic yet—only render the search input and result list.

**Step 2: Template**

- Search: `flux:input` with `wire:model.live.debounce.400ms="query"`, placeholder “Search RAWG for a game…”, `aria-label="Search RAWG"`.
- Wrapper for results: `wire:loading.flex` (or similar) to show a loading indicator while `query` is set and component is re-rendering; use `wire:target` if needed (e.g. debounced update).
- List: `@foreach` over the search results array. Each item: show `cover_image` (or placeholder), `title`, `release_date`, `platforms`/`genres` if present. Use existing Tailwind/Flux patterns from `game-search-modal` and admin layout (zinc, dark mode). Leave a placeholder for the “Add to database” button (e.g. a disabled button or “Add” text).
- Empty states: when `trim($query) === ''` show “Enter a game name to search RAWG.” When query is not empty and results are empty, show “No RAWG results. Try another query.”

**Step 3: Verify**

Visit `/admin/add-game` as an admin; type in search and confirm RAWG results appear (requires `RAWG_API_KEY` in `.env`). No “Add” action yet.

**Step 4: Commit**

```bash
git add resources/views/components/⚡admin-rawg-add-game.blade.php
git commit -m "feat: admin RAWG search Livewire component (search only)"
```

---

## Task 3: “Add to database” action and progress

**Files:**
- Modify: `resources/views/components/⚡admin-rawg-add-game.blade.php`

**Step 1: Add “already in DB” check**

For each result item, compute whether the game is already in the database: `Game::where('external_source', 'rawg')->where('external_id', $item['external_id'])->first()`. Expose this in the template (e.g. pass a map of `external_id => true` or compute per item in the loop). If already in DB, show “Already in database” (or a badge) and do not show the Add button, or show it disabled.

**Step 2: Add “Add to database” action**

Add a Livewire method, e.g. `addGame(string $externalId): void`. Inside: (1) Authorize: ensure current user is admin (e.g. `abort_unless(auth()->user()?->isAdmin(), 403)`). (2) Call `SyncGameJob::dispatchSync($externalId)` (or instantiate and `handle()` the job with the provider) so the add runs synchronously and the next render shows the game in DB. (3) Set a component property or flash message for success, e.g. `session()->flash('add-game-success', $externalId)` or a public `$addedExternalId`. (4) On exception, set an error message (e.g. `session()->flash('add-game-error', 'Could not add game. Try again.')` or public `$addError`).

**Step 3: Button and loading state**

In the template, for each result that is not already in DB: add a button “Add to database” that calls `wire:click="addGame('{{ $item['external_id'] }}')"`. Use `wire:loading wire:target="addGame"` (or scoped to that external_id if you use a single loading state) to show a spinner or “Adding…” and `wire:loading.attr="disabled"` on the button. After a successful add, the re-render will show that row as “Already in database.”

**Step 4: Success and error feedback**

Display success: e.g. “Added: {title}” for the last added game (from flash or `$addedExternalId`). Display error: e.g. “Could not add game. Try again.” Use `flux:feedback` or a simple div with `aria-live="polite"` so screen readers get the update.

**Step 5: Commit**

```bash
git add resources/views/components/⚡admin-rawg-add-game.blade.php
git commit -m "feat: add game to database with progress feedback"
```

---

## Task 4: Polish UI (stagger, accessibility)

**Files:**
- Modify: `resources/views/components/⚡admin-rawg-add-game.blade.php`

**Step 1: Stagger (optional)**

If the result list is rendered in one go, add a small stagger to list items (e.g. `animation-delay` per item via `style` or Tailwind arbitrary value) so they appear in sequence. Keep it subtle (e.g. 30–50 ms per item).

**Step 2: Accessibility**

Ensure search input has `aria-label="Search RAWG"`. Mark the results list with `aria-label="RAWG search results"`. For the loading state, use `aria-busy="true"` on the results container when loading. For success/error message container, use `aria-live="polite"` so the message is announced.

**Step 3: Commit**

```bash
git add resources/views/components/⚡admin-rawg-add-game.blade.php
git commit -m "feat: admin add-game UI polish and a11y"
```

---

## Task 5: Feature tests

**Files:**
- Create or modify: `tests/Feature/AdminAddGameTest.php`

**Step 1: Test guest and non-admin**

- Guest visits `route('admin.add-game')` → redirected to login (or 302 to login).
- Authenticated user with `UserRole::User` visits `route('admin.add-game')` → 403.

**Step 2: Test admin can see page**

- Authenticated admin visits `route('admin.add-game')` → 200 and response contains “Add game from RAWG” (or the Livewire component placeholder text).

**Step 3: Test add-game action (authorization)**

- As admin, post to the Livewire component endpoint (or use `Livewire::test()`) to call `addGame` with a valid RAWG external ID. Mock `GameDataProvider` or use a real key in testing to avoid flakiness; prefer mocking the provider in unit/feature test so the test does not call RAWG. Assert that after the action, a `Game` exists with `external_source = 'rawg'` and the given `external_id`, or assert success message in the response.
- As non-admin, attempt the same `addGame` call → 403 or unauthorized.

Use `User::factory()->admin()->create()` and `User::factory()->create()`. For the “add game” test, either mock `GameDataProvider::getGameDetails()` to return a fixed array and assert `Game::where(...)->exists()`, or dispatch sync and assert DB state.

**Step 4: Run tests**

```bash
php artisan test --compact tests/Feature/AdminAddGameTest.php
```

**Step 5: Commit**

```bash
git add tests/Feature/AdminAddGameTest.php
git commit -m "test: admin add game from RAWG"
```

---

## Task 6: Run full test suite and Pint

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
git commit -m "chore: style and fix tests for admin add game"
```

---

## Execution handoff

Plan saved to `docs/plans/2026-02-28-admin-add-game-from-rawg.md`.

**Next steps:** Execute task-by-task (e.g. with executing-plans skill), or implement manually in the order above. Ensure `RAWG_API_KEY` is set in `.env` for local and testing where real RAWG calls are allowed.
