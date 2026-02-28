# User Search (Spotlight-Style) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Improve the user-facing game search modal so it is openable via keyboard shortcut from anywhere, has a Spotlight-like look and feel (centered overlay, blurred backdrop, refined panel), and lets users track/untrack a game directly from the search result row without leaving the modal.

**Architecture:** The search modal already lives in `resources/views/components/⚡game-search-modal.blade.php` (Livewire) and is opened via `flux:modal.trigger` with `shortcut="meta+k"` in `resources/views/layouts/app.blade.php`. We verify that shortcut works globally; if Flux only binds to the trigger element, we add a document-level key listener that opens the modal by name. We restyle the modal and its content for Spotlight aesthetics (centered, rounded, backdrop blur, clear hierarchy). We add track/untrack actions in the Livewire component: per-result row shows “Track game” / “Remove from tracking” for authenticated users (using existing `GamePolicy` and `User::trackedGames()`), and “Log in to track” for guests; actions are Livewire methods so the modal stays open and the list re-renders with updated state. Optional: arrow-key navigation between results and Enter to open the focused game.

**Tech Stack:** Laravel 12, Livewire 4, Flux (flux:modal, flux:modal.trigger), Tailwind v4, Alpine (if needed for keyboard nav). No new dependencies.

---

## UI/UX Direction (frontend-design)

- **Purpose:** Quick game discovery and one-place action to open a game or track it. Used from any page.
- **Tone:** Spotlight-like: minimal, focused, fast. Centered overlay with a single clear task (search → pick or track). Refined and calm, not flashy.
- **Spotlight cues:**
  - **Overlay:** Centered panel (e.g. max-width 32rem), large rounded corners (e.g. rounded-2xl), subtle shadow. Backdrop: dimmed and blurred (e.g. bg-black/40 backdrop-blur-sm) so the rest of the page recedes.
  - **Search:** One search input at the top, prominent; placeholder “Search games…”; optional short hint “⌘K” near the trigger in the header only (already present).
  - **Results:** List directly below the input; each row: small cover (or initial), title, optional subtitle (release date, platforms). Row is a link to the game page; secondary action (Track / Remove from tracking) on the same row that does not navigate—stays in modal.
  - **Keyboard:** ⌘K (Mac) / Ctrl+K (Win/Linux) opens the modal from anywhere. Inside modal: Tab into input, type to search; optional ↑↓ to move highlight, Enter to open focused game (can be phased).
- **Accessibility:** `aria-label` on search and list; focus trap in modal (Flux usually handles); track/untrack buttons have clear labels; guest sees “Log in to track” (link to login).
- **Existing stack:** Keep Syne + DM Sans, zinc/cyan palette, dark mode (dark:) as in the rest of the app.

---

## Task 1: Verify keyboard shortcut and document it

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/GameSearchModalTest.php` (or new test file)

**Step 1: Verify current behavior**

The layout already has `flux:modal.trigger name="game-search" shortcut="meta+k"`. In the browser, from different pages (home, dashboard, game show), press ⌘K (or Ctrl+K). If the modal opens from anywhere, the shortcut is global and no code change is needed for this task beyond documentation.

**Step 2: If shortcut is not global, add document-level listener**

If the modal only opens when focus is on or near the trigger button, add a keydown listener on `document` that listens for `meta+k` or `ctrl+k`, prevents default, and opens the Flux modal by name. Check Flux docs (or Flux source) for how to open a modal programmatically (e.g. custom event or Alpine `$flux.modals.open('game-search')`). In `app.blade.php`, add a small inline script or a Vite entry that runs on every page and registers the listener. Ensure it does not fire when the user is typing in an input/textarea (e.g. skip when `event.target.closest('input, textarea')`).

**Step 3: Document shortcut in UI**

Ensure the header trigger button has `aria-label="Search games (⌘K)"` or similar (already “Search games (⌘K)” in the plan’s understanding). Add a short hint inside the modal (e.g. “Press ⌘K anytime to search”) only if it does not clutter the Spotlight look.

**Step 4: Write a simple test**

In `tests/Feature/GameSearchModalTest.php` (create if needed): test that the search modal route/component is rendered when the layout is loaded (e.g. visit `/` and assert the modal trigger or modal container exists in the HTML). Optionally, test that pressing the shortcut is possible (e.g. with a browser test or by asserting the trigger has the shortcut attribute). Prefer one minimal test that proves the modal is present and the shortcut attribute is set.

**Step 5: Run test**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 6: Commit**

```bash
git add resources/views/layouts/app.blade.php tests/Feature/GameSearchModalTest.php
git commit -m "chore: ensure game search modal shortcut works and is documented"
```

---

## Task 2: Spotlight-style modal layout and styling

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (modal container)
- Modify: `resources/views/components/⚡game-search-modal.blade.php` (inner structure and classes)

**Step 1: Backdrop and panel in layout**

In `app.blade.php`, the `flux:modal name="game-search"` may accept classes for the overlay. Target the modal’s overlay/backdrop to be dimmed and blurred (e.g. `bg-zinc-900/50 dark:bg-zinc-950/60 backdrop-blur-sm`) and the content wrapper to be centered with a max-width and large rounded corners (e.g. `max-w-2xl rounded-2xl`). Use Flux’s recommended way to style the modal (slot or class on the modal component). If Flux does not allow custom overlay classes easily, wrap the inner content in a div that provides the rounded panel look and ensure the default Flux overlay is at least dimmed.

**Step 2: Inner content structure in game-search-modal**

In `⚡game-search-modal.blade.php`, wrap the existing content in a container with padding and clear hierarchy:
- Top: search input, full width, no extra margin so it feels “attached” to the panel top.
- Below: scrollable results list (`max-h-[60vh] overflow-y-auto`) with consistent row styling.

**Step 3: Result row styling (Spotlight-like)**

Each result row: flex layout with gap; left: cover image or initial (fixed small size, rounded); center: title (font-medium) and optional subtitle (release date, platforms) in smaller/lighter text; right (optional): space for the track action. Use `divide-y` for separators. Hover/focus state: subtle background change (e.g. `hover:bg-zinc-100 dark:hover:bg-zinc-800`). Ensure the main row is a link to the game page; the track button will sit inside the row but with `@click.stop` or equivalent so clicking it does not follow the link.

**Step 4: Empty and no-results states**

When query is empty, show a short message (e.g. “Type to search games”) or leave the results area empty. When query is non-empty and there are no results, show “No games found.” in muted text, centered or left-aligned per design.

**Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/components/⚡game-search-modal.blade.php
git commit -m "style: Spotlight-style layout and styling for game search modal"
```

---

## Task 3: Track/untrack in search results (data and policy)

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php` (PHP and Blade)

**Step 1: Expose tracked state per result**

In the Livewire component’s PHP block, add a way to know which of the current results are tracked by the current user. Option A: a computed property that returns a list of tracked game IDs for the current user among `$this->results` (e.g. when `auth()->check()`, run `auth()->user()->trackedGames()->whereIn('game_id', $this->results->pluck('id'))->pluck('game_id')->all()` and use that in the view). Option B: add an `isTracked` (or similar) to each result in a single query to avoid N+1 (e.g. load results, then one query for tracked IDs for this user and pass to view). Use Option A or B; keep a single extra query when user is authenticated and results exist.

**Step 2: Add Livewire methods trackGame and untrackGame**

Add two public methods: `trackGame(int $gameId): void` and `untrackGame(int $gameId): void`. In each, resolve the game by ID (`Game::findOrFail($gameId)`), then call `$this->authorize('track', $game)` or `$this->authorize('untrack', $game)`, then `auth()->user()->trackedGames()->syncWithoutDetaching([$game->id])` or `detach($game->id)`. No redirect; Livewire will re-render and the same results will now show updated tracked state.

**Step 3: Blade – track/untrack button per row**

For each result row:
- If guest: show a link “Log in to track” pointing to `route('login')` (or login with return URL). Use `@click.stop` or equivalent if the row is a link so clicking “Log in to track” doesn’t follow the row link.
- If authenticated: if this game is tracked, show a button “Remove from tracking” that calls `wire:click="untrackGame({{ $game->id }})"` and use `wire:loading` to show a loading state (e.g. disabled + “…” or spinner). If not tracked, show “Track game” with `wire:click="trackGame({{ $game->id }})"` and same loading state. Use `wire:target="trackGame({{ $game->id }}), untrackGame({{ $game->id }})"` if needed so only that row shows loading. Prevent the click from bubbling to the row link (e.g. `@click.stop` on the button or use a small wrapper with `@click.stop`).

**Step 4: Keep primary row click to game page**

Ensure the row’s main click area (e.g. the `<a href="{{ route('games.show', $game) }}">`) still wraps the cover and text; the track/untrack control is a sibling or inside the row but with stopPropagation so it doesn’t trigger the link.

**Step 5: Run existing tests**

```bash
php artisan test --compact --filter=Tracking
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 6: Commit**

```bash
git add resources/views/components/⚡game-search-modal.blade.php
git commit -m "feat: track/untrack game from search modal result row"
```

---

## Task 4: Tests for track/untrack in modal

**Files:**
- Create or modify: `tests/Feature/GameSearchModalTest.php`

**Step 1: Write failing test – authenticated user can track from modal**

Render the Livewire game-search-modal (e.g. by visiting a page that includes it and opening the modal, or by testing the component in isolation with Livewire’s test helpers). Set a search query that returns at least one game. As an authenticated user, call `trackGame($game->id)` (e.g. `$this->livewire(..., ['query' => $game->title])->call('trackGame', $game->id)`) and assert the user’s `trackedGames` now includes that game.

**Step 2: Run test to verify it fails (if testing before implementation) or passes (if after Task 3)**

```bash
php artisan test --compact --filter=GameSearchModal
```

**Step 3: Write test – authenticated user can untrack from modal**

Same setup; attach the game to the user’s tracked list, then call `untrackGame($game->id)` and assert the game is no longer in the user’s tracked games.

**Step 4: Run tests**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 5: Commit**

```bash
git add tests/Feature/GameSearchModalTest.php
git commit -m "test: track/untrack from game search modal"
```

---

## Task 5: Optional – keyboard navigation in modal

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php`

**Step 1: Design**

When the modal is open, allow Arrow Down / Arrow Up to move a “highlight” (focus or data attribute) among the result items, and Enter to open the currently highlighted game (same as clicking the row). Escape should close the modal (Flux typically handles this). Do not change Tab behavior for the search input and track buttons.

**Step 2: Implement with Alpine or minimal JS**

Use Alpine on the modal container (or a wrapper): maintain a `highlightedIndex` (0-based). On keydown (when target is the modal container or the list), if key is ArrowDown increment index (capped at results length), if ArrowUp decrement (capped at 0), and set focus or scroll the highlighted item into view. On Enter, if an item is highlighted, navigate to `route('games.show', $results[highlightedIndex])` (e.g. `window.location = ...` or inject the URL from the server). Ensure the search input still receives typing; only when focus is on the list or the modal (and not on an input) should arrow keys apply. Alternatively use Flux/Livewire patterns if they provide list keyboard nav.

**Step 3: Accessibility**

Ensure the list has `role="listbox"` and items have `role="option"` and `aria-selected` when highlighted, or keep standard list semantics and use `tabindex="-1"` and `focus()` for the highlighted item so screen readers and keyboard users get correct behavior.

**Step 4: Commit (optional)**

```bash
git add resources/views/components/⚡game-search-modal.blade.php
git commit -m "feat: arrow-key navigation in game search modal"
```

---

## Summary

| Task | Description |
|------|-------------|
| 1 | Verify ⌘K/Ctrl+K opens modal globally; document and test. |
| 2 | Spotlight-style layout: backdrop blur, centered rounded panel, result row styling. |
| 3 | Track/untrack in modal: tracked state per result, `trackGame`/`untrackGame`, buttons in each row. |
| 4 | Feature tests for track/untrack from modal. |
| 5 | Optional: arrow-key navigation and Enter to open game. |

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-02-28-user-search-improvements.md`. Two execution options:

1. **Subagent-Driven (this session)** – I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run the plan task-by-task with checkpoints.

Which approach?
