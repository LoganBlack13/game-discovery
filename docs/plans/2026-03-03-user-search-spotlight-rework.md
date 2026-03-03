# User Search Spotlight/Raycast-Style Rework Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fully rework the user-facing search into a reusable Spotlight/Raycast-style command palette, accessible via a global keyboard shortcut from the app layout, showing up to 10 rich results (image, title, track action) for fast game discovery and tracking.

**Architecture:** Centralize user search into a dedicated Livewire-powered command palette component that is mounted in the main app layout and opened via a global keyboard shortcut (⌘K/Ctrl+K). The component will call a backend search service that returns at most 10 results including cover image, title, and tracking state, and will expose track/untrack actions without leaving the modal. The UI will use Tailwind v4 + DaisyUI to mimic Spotlight/Raycast: centered command palette, blurred backdrop, keyboard navigation, and reusable Blade/Livewire component that any layout can include.

**Tech Stack:** Laravel 12, Livewire 4, Eloquent, Tailwind v4, DaisyUI 5, Pest 4. No new PHP or JS dependencies.

---

## Task 1: Understand current user search and constraints

**Files:**
- Inspect: `resources/views/layouts/app.blade.php`
- Inspect: `resources/views/components/⚡game-search-modal.blade.php` (or existing user search modal/component)
- Inspect: `app/Livewire` search-related components (if any)
- Inspect: `routes/web.php`, `routes/api.php` for existing search routes

**Step 1: Find current user search entry points**

Search the codebase for existing user-facing search (e.g. search input in the header, Flux modal triggers, Livewire search components). Identify how users currently open search (click, keyboard shortcut, both) and what layout(s) it lives in.

**Step 2: Document current data flow and limitations**

Trace the current search flow end-to-end (view → component/controller → query → response). Note where result count is controlled (or not), how tracking actions are triggered (if present), and whether images/titles are consistently available. Capture limitations mapping to the new requirements: keyboard accessibility, visual style, 10-result cap, reusable component in layout.

**Step 3: Decide migration vs. replacement strategy**

Decide whether to (a) evolve the existing game search modal into the new Spotlight/Raycast-style command palette, or (b) introduce a new `UserSearchCommandPalette` component and deprecate older entry points. Prefer evolving the existing modal if it is already wired into the layout and Livewire, to minimize churn.

**Step 4: Commit (notes only, no code changes)**

Document findings in this plan file or a short internal note; no git commit required yet.

**Task 1 findings (execution):** User search entry point is the header button in `app.blade.php` that dispatches `open-game-search`; a fixed overlay wraps `<livewire:game-search-modal />`. No global ⌘K/Ctrl+K yet (button has aria-label "Search games (⌘K)" but no key listener). The modal is an anonymous Livewire component (PHP + Blade in one file) at `resources/views/components/⚡game-search-modal.blade.php`. Data flow: `getResultsProperty()` runs `Game::query()->where('title','like',...)->limit(10)->get()`; `getTrackedGameIdsProperty()` runs a second query for the user's tracked IDs. No dedicated service; no single-query eager loading for tracking. **Decision:** Evolve the existing modal—add `UserGameSearchService`, wire it into the existing component (resolve via `app()` in the anonymous class), add global keyboard shortcut in layout, keep same Blade/UI and enhance per plan.

---

## Task 2: Backend search service with 10-item cap and tracking info

**Files:**
- Create or modify: `app/Services/UserGameSearchService.php` (or similar service class)
- Modify: relevant Livewire search component in `app/Livewire` (e.g. `UserSearchCommandPalette` or existing search component)
- Tests: `tests/Feature/UserSearchServiceTest.php` or extend existing search feature tests

**Step 1: Define a dedicated search service**

Create or update a service class (e.g. `UserGameSearchService`) that exposes a method like `search(User $user, string $query, int $limit = 10): Collection`. The method should:
- Sanitize and normalize the query.
- Apply appropriate filters (e.g. only published/visible games).
- Order by relevance/weight (reuse existing logic if present).
- **Limit results strictly to 10 items** via query builder (`->limit(10)` / `->take(10)`), using the `$limit` parameter.

**Step 2: Include image, title, and tracking metadata**

Ensure each result includes:
- A primary cover image URL or null fallback.
- A human-readable title.
- Tracking state for the current user (e.g. boolean `is_tracked` or related model ID). Use relationships or a join to avoid N+1 queries, eager loading the user’s tracked games in one query.

**Step 3: Wire service into Livewire component**

Update the Livewire command palette/search component to depend on `UserGameSearchService` (via injected property or container resolution) and use it whenever the search query changes. Enforce the 10-result limit exclusively via the service (not ad-hoc `take()` calls inside the component) so behavior is centralized.

**Step 4: Write failing feature test for 10-result cap**

In a new or existing Pest feature test (e.g. `tests/Feature/UserSearchServiceTest.php` or a Livewire component test), seed at least 15 games that match a query, run the search as an authenticated user, and assert that:
- At most 10 results are returned.
- All results have title and image (where available).
- Tracking state is correctly surfaced when the user already tracks some of them.

**Step 5: Run tests and make them pass**

Run:

```bash
php artisan test --compact tests/Feature/UserSearchServiceTest.php
```

Fix code until tests pass.

**Step 6: Commit**

```bash
git add app/Services/UserGameSearchService.php app/Livewire tests/Feature/UserSearchServiceTest.php
git commit -m "feat: centralize user search in service with 10-result cap and tracking metadata"
```

---

## Task 3: Spotlight/Raycast-style command palette Livewire component

**Files:**
- Create or modify: `app/Livewire/UserSearchCommandPalette.php`
- Create or modify: `resources/views/livewire/user-search-command-palette.blade.php` (or equivalent)
- Optional shared partial: `resources/views/components/user/search-result-row.blade.php`
- CSS: `resources/css/app.css`
- Tests: `tests/Feature/UserSearchCommandPaletteTest.php`

**Step 1: Define Livewire component API**

Create `UserSearchCommandPalette` Livewire component with public properties and methods:
- `public string $query = '';`
- `public Collection $results;`
- `public ?int $highlightedIndex = null;` (for keyboard navigation).
- `public function updatedQuery()` (or `updatedQueryDebounced`) to call the search service.
- `public function track(int $gameId)` and `public function untrack(int $gameId)` for track actions.

Ensure the component always uses the search service’s 10-result cap and does not alter the limit locally.

**Step 2: Build the Spotlight/Raycast-style Blade view**

In `user-search-command-palette.blade.php`, implement a centered command palette layout:
- Backdrop: blurred and dimmed using existing Flux modal/backdrop styles or a dedicated wrapper (`backdrop-filter: blur(...)`, `bg-black/40` or `bg-black/60` with `dark:` variants).
- Panel: responsive width (full width with safe margin on mobile, at least 50% viewport width on `lg:`), rounded corners, soft shadow, and padding.
- Input: single prominent search input at the top with placeholder like “Search games” and a subtle hint of the keyboard shortcut (e.g. “⌘K” badge).
- Results list: scrollable column of up to 10 results beneath the input.

**Step 3: Rich result rows with image, title, and track action**

For each result row:
- Left: cover image (`object-cover`, fixed size, rounded) or placeholder with initial.
- Center: title (prominent) and optional subtitle (release year/platforms).
- Right: track/untrack button or “Log in to track” link, using Livewire actions (`wire:click="track({{ $game->id }})"` / `wire:click="untrack(...)"`) with loading state.

Make the row clickable for navigating to the game detail (e.g. `route('games.show', $game)`), with the track button using `@click.stop` so it does not trigger navigation.

**Step 4: Keyboard navigation inside the palette**

Add Alpine or Livewire-friendly key handling so that:
- Arrow Down/Up move the `highlightedIndex` between available results.
- Enter opens the highlighted result’s game detail.
- Escape closes the palette (delegated to the modal system if using Flux).

Apply a visible focus/hover/selected state to the highlighted row (background change, subtle border, no jarring effects).

**Step 5: CSS polish (Spotlight/Raycast feel)**

Update `resources/css/app.css` to:
- Define backdrop and panel-specific styles (blur, scale/fade-in animation, subtle motion on open).
- Add a small staggered fade/slide animation for result rows.
- Ensure dark mode works correctly with existing Tailwind/DaisyUI theme tokens.

**Step 6: Write feature tests for component behavior**

In `tests/Feature/UserSearchCommandPaletteTest.php`, add tests that:
- Render the component with an authenticated user and matching games; assert at most 10 results appear.
- Assert track/untrack actions update tracking state (e.g. via database assertions or Livewire’s `assertSee`/`assertDontSee` for button labels).
- Assert guest users see a “Log in to track” affordance instead of track/untrack buttons.

**Step 7: Run tests and commit**

```bash
php artisan test --compact tests/Feature/UserSearchCommandPaletteTest.php
```

Then:

```bash
git add app/Livewire resources/views/livewire resources/css/app.css tests/Feature/UserSearchCommandPaletteTest.php
git commit -m "feat: user search command palette with rich 10-result list and track actions"
```

---

## Task 4: Integrate command palette as reusable layout component with keyboard shortcut

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/layouts/*` other user-facing layouts if needed
- JS (if applicable): Alpine/inline script in layout
- Tests: `tests/Feature/LayoutUserSearchIntegrationTest.php`

**Step 1: Mount the component in the app layout**

In `app.blade.php`, include the `UserSearchCommandPalette` component once (e.g. inside the `<body>` but outside page-specific content). If the project already uses a Flux modal for search, wrap the command palette inside that modal element so we reuse Flux’s modal open/close behavior.

**Step 2: Implement global keyboard shortcut**

Add a small script (preferably Alpine `x-init` or a layout-level event listener) that:
- Listens for `keydown` on `document`.
- When the user presses ⌘K on macOS or Ctrl+K on other platforms **outside of text inputs**, prevents the default browser search and opens the command palette modal (e.g. via Flux’s JS API or dispatching the correct event).
- Ensures the search input inside the palette receives focus when opened.

**Step 3: Make shortcut discoverable and accessible**

Update any header search trigger button to:
- Include `aria-label="Search games (⌘K)"` or similar accessible text.
- Optionally display a small keyboard-hint badge (“⌘K” / “Ctrl+K”).

Ensure that the component is reusable by exposing any needed props (e.g. placeholder, search scope) so other layouts can drop it in with minimal configuration.

**Step 4: Write layout integration tests**

In `tests/Feature/LayoutUserSearchIntegrationTest.php`, add tests that:
- Render a page using the app layout and assert the Livewire command palette’s root element is present in the HTML.
- Assert the header contains a search trigger with the keyboard shortcut hint or `aria-label`.

Note: Keyboard event behavior is best covered by browser tests; keep these feature tests focused on server-rendered HTML.

**Step 5: Run tests and commit**

```bash
php artisan test --compact tests/Feature/LayoutUserSearchIntegrationTest.php
```

Then:

```bash
git add resources/views/layouts app/Livewire tests/Feature/LayoutUserSearchIntegrationTest.php
git commit -m "feat: integrate user search command palette into app layout with global shortcut"
```

---

## Task 5: Browser-level UX validation (optional but recommended)

**Files:**
- Tests: `tests/Browser/UserSearchCommandPaletteBrowserTest.php`

**Step 1: Add a Pest browser test for keyboard-driven search**

Create a browser test that:
- Visits a typical user page (e.g. dashboard or home).
- Presses the keyboard shortcut (⌘K or Ctrl+K).
- Asserts the command palette appears and the search input is focused.
- Types a query and asserts that at most 10 results render with image, title, and track action.
- Uses the keyboard (Arrow Down/Up + Enter) to open a game detail page.

**Step 2: Add a browser test for tracking from results**

Extend the browser test to:
- Ensure the user is authenticated.
- Open the palette, search for a game, click the track action on a result.
- Assert that the game is marked as tracked (via visual state or backend assertion if feasible).

**Step 3: Run browser tests and commit**

Run:

```bash
php artisan test --compact tests/Browser/UserSearchCommandPaletteBrowserTest.php
```

Then:

```bash
git add tests/Browser/UserSearchCommandPaletteBrowserTest.php
git commit -m "test: browser coverage for user search command palette keyboard UX"
```

---

## Summary

| Task | Description |
|------|-------------|
| 1 | Analyze existing user search implementation and constraints to choose migration path. |
| 2 | Implement a dedicated backend search service with a strict 10-result cap and tracking metadata. |
| 3 | Build a Spotlight/Raycast-style Livewire command palette component with rich results and track actions. |
| 4 | Integrate the command palette as a reusable app layout component with a global keyboard shortcut. |
| 5 | (Optional) Add browser tests to validate keyboard-driven search UX end-to-end. |

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-03-03-user-search-spotlight-rework.md`. Two execution options:

1. **Subagent-Driven (this session)** – Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run the plan task-by-task with checkpoints.

Which approach?

