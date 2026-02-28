# Admin Dashboard Improvements Implementation Plan

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** Improve the admin dashboard with top stats, in-dashboard search/import (Livewire, no full-page flow), a “latest games” table with “See more,” and a dedicated admin games index with search, filter, row actions (delete, edit), and edit via a slide-over drawer.

**Architecture:** Dashboard becomes the main admin hub: stats cards at top, inline Livewire “Add game from RAWG” (reuse/relocate existing component), and a “Latest games” table (10 rows) with link to `/admin/games`. New admin games index page uses a Livewire component (or controller + Livewire table) for listing with search/filter and row actions; edit opens a Flux-styled slide-over (right or bottom) with a form to update game attributes. Authorization: admin-only middleware and policy for update/delete on Game.

**Tech Stack:** Laravel 12, Livewire 4, Flux (flux:modal for slide-over styling or modal), Tailwind v4, Pest. No new dependencies.

---

## UI/UX Direction (frontend-design)

- **Tone:** Utilitarian / industrial; align with existing admin (Syne + DM Sans, zinc, light/dark). Clear hierarchy, distinct states, restrained motion.
- **Stats:** Compact cards at top (e.g. Total games, Added this week). Use `flux:card` or simple grid; no decorative clutter.
- **Search/import on dashboard:** Inline on dashboard (no mandatory navigation to a separate page). Collapsible “Add game from RAWG” section or always-visible compact block: search input, debounced results, “Add to database” per row. Same behavior as current `admin-rawg-add-game` component; just embed on dashboard and optionally remove or repurpose the standalone “Add game” page (e.g. keep route for deep-link, but primary entry is dashboard).
- **Latest games table:** Table with columns: cover (thumbnail), title, release date, source (e.g. RAWG). No row actions on dashboard. “See more” button links to `route('admin.games.index')`.
- **Admin games index:** Full-page table with search (by title/slug), optional filters (e.g. source, date range). Each row: edit (opens drawer), delete (confirm then delete). Use `flux:table` and existing Tailwind/Flux patterns.
- **Edit game drawer:** Slide-over from right (or bottom on small viewports). Content: form (title, slug, description, cover_image, developer, publisher, genres, platforms, release_date, release_status). Submit updates game and closes drawer. Use `flux:modal` with custom positioning (e.g. `class="... fixed inset-y-0 right-0 w-full max-w-lg ..."`) to achieve drawer look, or a dedicated Flux pattern if available in the project’s Flux version.
- **Accessibility:** `aria-label` on search/filters, `aria-live` for success/error, focus trap in drawer, confirm step for delete.

---

## Task 1: Dashboard stats – data and view

**Files:**
- Modify: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `resources/views/admin/dashboard.blade.php`

**Step 1: Pass stats from controller**

In `DashboardController::__invoke()`, query: total games count, count of games created in the last 7 days (e.g. `Game::where('created_at', '>=', now()->subDays(7))->count()`). Pass to view as `['totalGames' => $total, 'recentGamesCount' => $recent]` (or single `stats` array).

**Step 2: Render stats on dashboard**

In `resources/views/admin/dashboard.blade.php`, add a stats section at the top: two (or three) compact cards in a grid (e.g. `grid grid-cols-1 sm:grid-cols-2 gap-4`). Each card: label (e.g. “Total games”, “Added this week”) and value. Use existing admin styling (zinc, dark mode). Prefer simple divs or `flux:card` if already used elsewhere in admin.

**Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/DashboardController.php resources/views/admin/dashboard.blade.php
git commit -m "feat: admin dashboard stats (total and recent games)"
```

---

## Task 2: Inline “Add game from RAWG” on dashboard

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `routes/web.php` (optional: keep `admin.add-game` route for deep-link)

**Step 1: Embed Livewire component on dashboard**

In `resources/views/admin/dashboard.blade.php`, add a section “Add game from RAWG” and include `<livewire:admin-rawg-add-game />` so the search-and-add UI is directly on the dashboard. Remove or repurpose the standalone “Add game from RAWG” button that linked to `admin.add-game` (replace with anchor to `#add-game` or remove; keep one clear entry point). Ensure the component is visible without a full-page navigation (e.g. collapsible with a “Search RAWG & add game” toggle, or always visible below stats).

**Step 2: Optional – collapse/expand**

If desired, wrap the Livewire component in a simple collapse (e.g. Alpine `x-data="{ open: false }"` and toggle button) so the dashboard stays scannable; default can be collapsed. Follow existing project patterns for collapse (e.g. Flux or Alpine).

**Step 3: Keep add-game route (optional)**

Keep `Route::get('/add-game', ...)->name('add-game')` so `/admin/add-game` still works (e.g. same view with only the Livewire component, or redirect to `admin.dashboard` with fragment `#add-game`). Document in plan: primary entry is dashboard.

**Step 4: Commit**

```bash
git add resources/views/admin/dashboard.blade.php routes/web.php
git commit -m "feat: inline Add game from RAWG on admin dashboard"
```

---

## Task 3: Latest games table on dashboard (10 rows + See more)

**Files:**
- Modify: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `resources/views/admin/dashboard.blade.php`

**Step 1: Load latest games in controller**

In `DashboardController::__invoke()`, add `$latestGames = Game::query()->latest()->limit(10)->get()`. Pass to view as `latestGames`.

**Step 2: Render table on dashboard**

In `resources/views/admin/dashboard.blade.php`, add a “Latest games” section. Table columns: cover (small thumbnail or placeholder), title (link to `route('games.show', $game)` if desired), release_date (formatted), external_source (e.g. “RAWG”). Use a simple `<table>` or `flux:table` with `flux:table.columns` / `flux:table.rows` / `flux:table.row` / `flux:table.cell` as per Flux table API. No row actions on dashboard (only on full games index).

**Step 3: “See more” button**

Add a button or link “See more” that points to `route('admin.games.index')` (to be added in Task 5). Use `flux:button` or styled anchor consistent with admin.

**Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/DashboardController.php resources/views/admin/dashboard.blade.php
git commit -m "feat: admin dashboard latest games table and See more link"
```

---

## Task 4: Game policy – update and delete (admin only)

**Files:**
- Modify: `app/Policies/GamePolicy.php`
- Register policy if not already (e.g. in `AuthServiceProvider` or auto-discovery)

**Step 1: Add update and delete**

In `GamePolicy`, add `update(User $user, Game $game): bool` and `delete(User $user, Game $game): bool`. Both return `$user->isAdmin()` (admin-only). Use existing `UserRole` and `isAdmin()`.

**Step 2: Ensure policy used**

Ensure `Game` model uses `AuthorizesRequests` and `$this->authorize('update', $game)` / `$this->authorize('delete', $game)` will be used in controller/ Livewire (next tasks). Policy auto-discovery is default in Laravel for `Game` => `GamePolicy`.

**Step 3: Commit**

```bash
git add app/Policies/GamePolicy.php
git commit -m "feat: Game policy update and delete for admins"
```

---

## Task 5: Admin games index route and page shell

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Admin/GameController.php` (resource-style or invokable index)
- Create: `resources/views/admin/games/index.blade.php`

**Step 1: Route**

In `routes/web.php`, inside the admin group, add: `Route::get('/games', [App\Http\Controllers\Admin\GameController::class, 'index'])->name('games.index');`. Name: `admin.games.index`, path: `/admin/games`.

**Step 2: Controller**

Create `Admin\GameController` with `index()` method. Pass to view: paginated games (e.g. `Game::query()->latest()->paginate(20)`), and any initial filter/search params (empty for now). Return view `admin.games.index`.

**Step 3: View shell**

Create `resources/views/admin/games/index.blade.php` using `<x-layouts.admin title="Manage games">`. Content: page title “Manage games”, placeholder for table and filters (next task). Include a Livewire component placeholder for the games table with search/filter/actions (e.g. `<livewire:admin-games-list />`) so the next task can fill it in.

**Step 4: Commit**

```bash
git add routes/web.php app/Http/Controllers/Admin/GameController.php resources/views/admin/games/index.blade.php
git commit -m "feat: admin games index route and page shell"
```

---

## Task 6: Admin games list Livewire component – table, search, filter

**Files:**
- Create: `app/Livewire/Admin/GamesList.php` (or anonymous component in `resources/views/components/⚡admin-games-list.blade.php`)
- Create or update: `resources/views/components/⚡admin-games-list.blade.php` (if class-based, create `resources/views/livewire/admin/games-list.blade.php`)

**Step 1: Component logic**

Public properties: `$search = ''`, `$source = ''` (e.g. external_source filter: all, rawg). Computed or method: filtered query `Game::query()->when($search, fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'))->when($source, fn ($q) => $q->where('external_source', $source))->latest()->paginate(20)`. Use `#[Computed]` or `$this->getGamesProperty()` and paginate in Livewire (e.g. `Livewire\WithPagination`).

**Step 2: Template – search and filters**

Search input: `wire:model.live.debounce.300ms="search"`, placeholder “Search by title or slug”. Optional: dropdown or radios for “Source” (All, RAWG). Use `flux:input` and existing admin styling.

**Step 3: Template – table**

Use `flux:table` with columns: Cover, Title, Release date, Source, Actions. Rows: `@foreach($this->games as $game)` (or paginated collection). Actions column: Edit button (wire:click to open edit drawer, e.g. `openEdit({{ $game->id }})`), Delete button (wire:click with confirm or separate step). No persistence of edit form yet (next task).

**Step 4: Pagination**

Use `flux:pagination` with the paginator from the computed list, or Livewire’s `links()` in the view.

**Step 5: Commit**

```bash
git add app/Livewire/Admin/GamesList.php resources/views/livewire/admin/games-list.blade.php
# OR
git add resources/views/components/⚡admin-games-list.blade.php
git commit -m "feat: admin games list Livewire table with search and filter"
```

---

## Task 7: Delete game action

**Files:**
- Modify: `app/Livewire/Admin/GamesList.php` (or anonymous component)
- Modify: `resources/views/livewire/admin/games-list.blade.php` (or `⚡admin-games-list.blade.php`)

**Step 1: Delete method**

Add method `deleteGame(Game $game): void`. Authorize: `$this->authorize('delete', $game)`. Then `$game->delete()`. Optionally flash message “Game deleted.” Use `Game` model and policy.

**Step 2: Confirm before delete**

In the view, either use a Flux confirm dialog (if available) or `wire:confirm="Are you sure you want to delete this game?"` (Livewire 3 confirm directive) on the delete button that calls `deleteGame({{ $game->id }})` or pass slug/id. Ensure the button calls the Livewire method with the correct game.

**Step 3: Commit**

```bash
git add app/Livewire/Admin/GamesList.php resources/views/livewire/admin/games-list.blade.php
git commit -m "feat: admin delete game with confirmation"
```

---

## Task 8: Edit game drawer – open/close and form shell

**Files:**
- Modify: `app/Livewire/Admin/GamesList.php` (or create `app/Livewire/Admin/EditGameDrawer.php` as child or embedded component)
- Create or modify: view for edit form (e.g. in same games-list view or partial)

**Step 1: Drawer state**

Add property `$editingGameId = null`. Method `openEdit(int $id): void` sets `$editingGameId = $id`; method `closeEdit(): void` sets `$editingGameId = null`. In view, when `$editingGameId` is set, show a drawer (see Step 2).

**Step 2: Drawer UI**

Use `flux:modal` with a fixed right-side panel style: e.g. `flux:modal name="edit-game"` and trigger open via Livewire (e.g. `$this->dispatch('open-edit-drawer')` and Alpine/Flux listening, or render the drawer content inside the Livewire view and control visibility with `@if($editingGameId)`. Recommended: one `flux:modal` in the Livewire view, with `name="edit-game-{{ $editingGameId }}"` or a single modal that receives the game. Use class to position as right-side drawer: e.g. `class="fixed inset-y-0 right-0 w-full max-w-lg ..."` on the modal content container so it slides from the right. Ensure focus trap and close button (calls `closeEdit()`).

**Step 3: Form shell**

Inside the drawer, show a form with fields: title, slug (optional, can be auto from title), description (textarea), cover_image (url text input), developer, publisher, genres (e.g. comma-separated or tag input), platforms (same), release_date (date input), release_status (select). Form submit button “Save” (next task). Load `Game::find($editingGameId)` and bind to public properties or a single `$editGame` array; prefer a dedicated Livewire component for the form to keep state clean (e.g. `Admin\EditGameForm` with `$game` and `$fields`).

**Step 4: Commit**

```bash
git add app/Livewire/Admin/GamesList.php resources/views/livewire/admin/games-list.blade.php
git commit -m "feat: admin edit game drawer shell and form fields"
```

---

## Task 9: Update game – form request and save

**Files:**
- Create: `app/Http/Requests/Admin/UpdateGameRequest.php`
- Modify: `app/Livewire/Admin/GamesList.php` or `EditGameForm` component
- Modify: `app/Models/Game.php` (ensure fillable includes all editable fields)

**Step 1: Form request**

Create `UpdateGameRequest`: rules for title (required, string, max), slug (nullable, string, unique:games,slug,{{ $game->id }}), description (nullable, string), cover_image (nullable, url), developer, publisher (nullable, string), genres (array, nullable), platforms (array, nullable), release_date (nullable, date), release_status (required, in:enum). Authorize: only admin (e.g. `auth()->user()?->isAdmin()`).

**Step 2: Save method**

In the component that holds the edit form, add `save(): void`. Load game by `$editingGameId`, authorize `$this->authorize('update', $game)`, validate using `UpdateGameRequest` (e.g. `$this->validate(...)` with same rules or inject request). Then `$game->update($validated)`. Set `$editingGameId = null` to close drawer, flash “Game updated.”

**Step 3: Bind form fields**

Ensure each form input is bound to a Livewire property (e.g. `$title`, `$slug`, …) or to an array `$form`; on `openEdit($id)` load the game and set these properties; on save use them for validation and update.

**Step 4: Commit**

```bash
git add app/Http/Requests/Admin/UpdateGameRequest.php app/Livewire/Admin/GamesList.php resources/views/livewire/admin/games-list.blade.php
git commit -m "feat: admin update game validation and save"
```

---

## Task 10: Dashboard “See more” link and nav

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `resources/views/layouts/admin.blade.php` (optional)

**Step 1: Link target**

Ensure “See more” in Task 3 points to `route('admin.games.index')`. Verify route name is `admin.games.index`.

**Step 2: Optional – admin nav**

In `resources/views/layouts/admin.blade.php`, add a “Games” link to `route('admin.games.index')` next to “Dashboard” so admins can reach the full list from the header.

**Step 3: Commit**

```bash
git add resources/views/admin/dashboard.blade.php resources/views/layouts/admin.blade.php
git commit -m "feat: admin nav link to games index"
```

---

## Task 11: Feature tests

**Files:**
- Create or modify: `tests/Feature/AdminDashboardTest.php`
- Create: `tests/Feature/AdminGamesIndexTest.php`
- Create: `tests/Feature/AdminGameEditTest.php` (or fold into AdminGamesIndexTest)

**Step 1: Dashboard stats and inline add-game**

As admin, get `route('admin.dashboard')`. Assert response contains total games count (or “Total games”), “Added this week” (or recent count), and the Livewire add-game component (e.g. “Search RAWG” or component placeholder). Assert “See more” link present and href is `route('admin.games.index')`.

**Step 2: Games index – auth and list**

Guest → redirect to login. Non-admin → 403. Admin → 200, page contains “Manage games” and table (or Livewire list). Assert at least one game row if DB has games (use factory).

**Step 3: Games index – search and filter**

As admin, visit games index with `?search=foo`. Assert query is applied (mock or real DB with Game::factory). Optional: filter by source.

**Step 4: Delete game**

As admin, call Livewire delete action for a game (e.g. `Livewire::test('admin.games-list')->call('deleteGame', $game)`). Confirm game is deleted (assert `Game::find($id)` is null) and success message. As non-admin, assert 403.

**Step 5: Update game**

As admin, open edit for a game, set new title, save. Assert game title updated in DB and drawer closes (or redirect). As non-admin, assert 403 for update.

**Step 6: Run tests**

```bash
php artisan test --compact tests/Feature/AdminDashboardTest.php tests/Feature/AdminGamesIndexTest.php
```

**Step 7: Commit**

```bash
git add tests/Feature/AdminDashboardTest.php tests/Feature/AdminGamesIndexTest.php
git commit -m "test: admin dashboard and games index"
```

---

## Task 12: Run full test suite and Pint

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
git commit -m "chore: style and fix tests for admin dashboard improvements"
```

---

## Execution handoff

Plan saved to `docs/plans/2026-02-28-admin-dashboard-improvements.md`.

**Next steps:** Execute task-by-task (e.g. with executing-plans skill), or implement manually in the order above.

**Two execution options:**

1. **Subagent-Driven (this session)** – Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run with checkpoints.

Which approach?
