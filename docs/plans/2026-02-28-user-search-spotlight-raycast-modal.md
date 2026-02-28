# User Search Spotlight/Raycast-Style Modal Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Turn the user-facing game search into a real Spotlight/Raycast-style modal: blurred backdrop, responsive sizing (≥50% width on lg, full width on mobile/tablet), dynamic search with animations, and glossy result rows with cover, title, and track action—all with a distinctive, production-grade UI/UX.

**Architecture:** The search modal lives in `resources/views/components/⚡game-search-modal.blade.php` (Livewire) and is opened via `flux:modal.trigger` with `shortcut="meta+k"` in `resources/views/layouts/app.blade.php`. We enhance the Flux modal: (1) strengthen backdrop blur and enforce responsive width (full on mobile/tablet, min 50% on lg) via layout classes and `resources/css/app.css`; (2) keep dynamic search (existing `wire:model.live.debounce`) and add CSS/JS animations for input and result list (staggered reveals, subtle transitions); (3) redesign result rows to be glossy and visual (cover image, title, metadata, track/untrack action without leaving the modal); (4) ensure ⌘K/Ctrl+K opens the modal globally and document it; (5) retain track/untrack from result with tests. Optional: arrow-key navigation and Enter to open focused game.

**Tech Stack:** Laravel 12, Livewire 4, Flux (flux:modal, flux:modal.trigger), Tailwind v4, Alpine (keyboard nav if implemented). No new dependencies.

---

## UI/UX Direction (frontend-design)

- **Purpose:** Fast game discovery and one-place action: search → open game or track it. Used from any page. Feels like a command palette, not a form.
- **Tone:** Spotlight/Raycast-like: minimal, focused, fast. Refined and calm with one memorable visual hook (glossy result cards and smooth motion). Not flashy chaos—intentional polish.
- **Differentiation:** The one thing users remember: the modal feels like a native command palette (blur, scale, responsive width) and results look premium (cover art, clear hierarchy, instant track action).

### Layout & Backdrop

- **Backdrop:** Always blurred and dimmed so the rest of the page recedes. Use `::backdrop` in CSS: `backdrop-filter: blur(...)` and semi-opaque background (e.g. `bg-black/40` light, `bg-black/60` dark). Existing `resources/css/app.css` already targets `[data-flux-modal] > dialog[data-modal="game-search"]::backdrop`; strengthen blur (e.g. `blur(0.5rem)` or `blur(8px)`) and ensure it applies on open.
- **Modal panel:** Centered. Responsive width:
  - **Mobile & tablet (< lg):** Full width with comfortable horizontal margin (e.g. `w-full max-w-[calc(100%-2rem)]` or similar so it doesn’t touch edges).
  - **Large (lg and up):** At least 50% of viewport width (e.g. `min-w-[50vw]` or `w-full max-w-2xl` replaced with `min-w-[50vw] max-w-2xl` or `w-[min(100%,max(50vw,32rem))]`). Use Tailwind breakpoint `lg:` so one set of classes applies below lg (full width) and another at lg+ (min 50% width).
- **Panel styling:** Large rounded corners (e.g. `rounded-2xl`), subtle shadow, clear separation from backdrop. Inner content: no double rounding; padding consistent.

### Search Input & Dynamic Behavior

- **Input:** Single search field at top, prominent; placeholder “Search games…”; optional hint “⌘K” in header only. Focus trapped in modal when open (Flux default).
- **Dynamic search:** Keep Livewire `wire:model.live.debounce.300ms="query"`. Animations to improve UX:
  - **While typing / loading:** Optional subtle pulse or skeleton on the results area, or a short “Searching…” state with a minimal spinner, so the UI feels responsive.
  - **Results in:** When results appear, use a quick stagger (e.g. `animation-delay` per row or a single list fade/slide) so the list doesn’t pop in abruptly. Prefer CSS-only (e.g. `@keyframes` + `animation` and `animation-delay` on list items) to keep it simple.
  - **Empty / no results:** “Type to search games” when query is empty; “No games found.” when there are no results—muted, non-jarring.

### Result Rows (Sexy & Glossy)

- **Visual:** Each row is a card-like result: cover image (or initial placeholder), title, optional subtitle (release date, platforms). “Glossy” = subtle depth: light border or soft shadow, optional very subtle gradient overlay on the cover for legibility, and a clear hover/focus state (e.g. background change + slight scale or glow). Use existing Syne + DM Sans and zinc/cyan palette; ensure dark mode (e.g. `dark:`).
- **Layout:** Flex or grid: left = cover (fixed size, rounded, object-cover); center = title (font-medium) + subtitle (smaller, muted); right = track action. Row is clickable to go to game page; track/untrack is a separate control that doesn’t navigate (e.g. `@click.stop` / `wire:click`).
- **Track action:** Authenticated: “Track game” / “Remove from tracking” with loading state. Guest: “Log in to track” link. Action stays in modal and re-renders the list state.

### Motion (frontend-design)

- **High-impact moments:** (1) Modal open: optional short scale/fade of the panel. (2) Results list: staggered fade-in or slide-up for each row (CSS `animation` + `animation-delay`). Avoid scattered micro-interactions; one cohesive motion system.
- **Hover/focus:** Subtle background and optional scale on result rows; track button clearly visible on hover.

### Accessibility

- `aria-label` on search input and results list; focus trap in modal (Flux); track/untrack buttons have clear labels; guest sees “Log in to track” with meaningful link text.

### Existing Stack

- Keep Syne + DM Sans, zinc/cyan (`--color-accent`), dark mode (`dark:`) consistent with the rest of the app. No new fonts or theme colors unless the plan explicitly adds one accent for the modal only.

---

## Task 1: Backdrop blur and responsive modal width

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Strengthen backdrop blur in CSS**

In `resources/css/app.css`, in the existing `[data-flux-modal] > dialog[data-modal="game-search"]::backdrop` block, increase blur (e.g. `backdrop-filter: blur(0.5rem)` or `blur(8px)`) so the background is clearly blurred when the modal is open. Ensure the dark variant also uses a strong enough blur. Keep background color dimmed (e.g. `rgba(0,0,0,0.4)` light, `rgba(0,0,0,0.6)` dark).

**Step 2: Set responsive modal width in layout**

In `resources/views/layouts/app.blade.php`, on the `flux:modal name="game-search"` element, set classes so that:
- Below `lg`: modal is full width with safe margin (e.g. `w-[calc(100%-2rem)]` or `max-w-[calc(100%-2rem)]` plus `mx-auto` so it’s centered and doesn’t touch edges).
- At `lg` and up: modal uses at least 50% of viewport width (e.g. `lg:min-w-[50vw] lg:max-w-2xl` or `lg:w-[min(100%,max(50vw,32rem))]`). Use Flux’s recommended class target (e.g. `[:where(&)]:...` if that’s how the project styles the modal content wrapper). Verify in Flux docs or existing modal class usage in the file.

**Step 3: Run frontend build**

```bash
bun run build
```

**Step 4: Commit**

```bash
git add resources/css/app.css resources/views/layouts/app.blade.php
git commit -m "style: blur backdrop and responsive width for game search modal"
```

---

## Task 2: Dynamic search animations (result list and states)

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php`
- Modify: `resources/css/app.css` (optional; add keyframes if not inline)

**Step 1: Add CSS keyframes for result list**

In `resources/css/app.css`, add a keyframe (e.g. `search-result-in`) for result rows: e.g. from `opacity: 0; transform: translateY(0.25rem)` to `opacity: 1; transform: translateY(0)`. Duration ~0.2–0.25s, ease-out. Add a class (e.g. `.search-result-item`) that applies this animation with a small delay per child (e.g. `animation-delay: calc(var(--i, 0) * 0.03s)`). Use a CSS variable or nth-child so each row can have a staggered delay.

**Step 2: Apply staggered animation to result list items**

In `⚡game-search-modal.blade.php`, on each result `<li>`, add the animation class and, if needed, a style or data attribute for index (e.g. `style="animation-delay: {{ $loop->index * 0.03 }}s"` or use a single wrapper with class that uses `:nth-child` for delay). Ensure the list container has `overflow` and layout so the animation is visible.

**Step 3: Optional “searching” or loading state**

If `$this->query` is non-empty and results are loading (Livewire request in flight), optionally show a minimal loading indicator (e.g. small spinner or “Searching…” text) so the UI feels dynamic. This can be done with `wire:loading` on a container. Keep it subtle so it doesn’t conflict with the staggered result-in animation.

**Step 4: Empty and no-results copy**

Ensure empty state: “Type to search games.” When query is set and results are empty: “No games found.” Both muted, consistent with existing copy in the component.

**Step 5: Run tests**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 6: Commit**

```bash
git add resources/views/components/⚡game-search-modal.blade.php resources/css/app.css
git commit -m "feat: animate game search result list for better UX"
```

---

## Task 3: Glossy result rows (picture, title, track action)

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php`

**Step 1: Redesign result row structure**

Per result row: use a card-like layout with clear hierarchy.
- **Cover:** Fixed size (e.g. `h-16 w-12` or `h-14 w-11`), rounded (e.g. `rounded-lg`), `object-cover`. If no cover, show initial letter in a rounded box with muted background. Optional: subtle ring or shadow for “glossy” depth.
- **Title:** Prominent (e.g. `font-medium` or `font-semibold`), single line with truncation (`truncate` or `line-clamp-1`).
- **Subtitle:** Release date and/or platforms in smaller, muted text (`text-sm text-zinc-500 dark:text-zinc-400`).
- **Track action:** On the right: for guests, “Log in to track” link; for authenticated, “Track game” or “Remove from tracking” button with `wire:click` and `wire:loading.attr="disabled"` and `wire:target` so only that row shows loading. Use `@click.stop` (or equivalent) on the action so clicking it doesn’t follow the row link.

**Step 2: Add glossy styling**

Apply to each row: subtle background on hover/focus (e.g. `hover:bg-zinc-100 dark:hover:bg-zinc-800`), optional `rounded-lg` and very subtle border or shadow (`border border-zinc-200/50 dark:border-zinc-700/50` or `shadow-sm`) so rows feel like cards. Ensure the main row area is a link to `route('games.show', $game)`; the track control is outside or inside with click stopped.

**Step 3: Ensure spacing and alignment**

Use flex with `gap` (e.g. `gap-3` or `gap-4`) between cover, text block, and action. Align items center vertically. Padding (e.g. `px-4 py-3`) so rows are tappable and readable.

**Step 4: Run tests**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 5: Commit**

```bash
git add resources/views/components/⚡game-search-modal.blade.php
git commit -m "style: glossy result rows with cover, title, and track action"
```

---

## Task 4: Keyboard shortcut and documentation

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (if needed)
- Modify or create: `tests/Feature/GameSearchModalTest.php`

**Step 1: Verify global shortcut**

In the browser, from multiple pages (home, dashboard, game show), press ⌘K (Mac) or Ctrl+K (Win/Linux). If the game-search modal opens from anywhere, the shortcut is already global; document that in the plan/task. If it only opens when focus is on the trigger, add a document-level `keydown` listener that prevents default and opens the Flux modal by name (e.g. `$flux.modals.open('game-search')` or the event Flux expects). Skip when the target is an `input` or `textarea`.

**Step 2: Document shortcut in UI**

Ensure the header trigger has `aria-label="Search games (⌘K)"` (or equivalent). Optionally add a short hint inside the modal (e.g. “Press ⌘K to search anytime”) only if it doesn’t clutter the Spotlight look.

**Step 3: Test modal presence and shortcut attribute**

In `tests/Feature/GameSearchModalTest.php`, ensure a test visits a page that uses the app layout and asserts the modal trigger exists and has the shortcut attribute (e.g. `shortcut="meta+k"` or the attribute your Flux version uses). Optionally assert the modal container for `game-search` is present in the DOM.

**Step 4: Run test**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php tests/Feature/GameSearchModalTest.php
git commit -m "chore: ensure game search modal shortcut works and is documented"
```

---

## Task 5: Track/untrack from result and tests

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php` (if not already done in Task 3)
- Modify: `tests/Feature/GameSearchModalTest.php`

**Step 1: Confirm track/untrack in component**

Ensure the Livewire component has: `getTrackedGameIdsProperty()`, `trackGame(int $gameId)`, `untrackGame(int $gameId)`, and that each result row shows the correct action (guest: “Log in to track”; auth: “Track game” or “Remove from tracking”) with `wire:click` and loading state. Row link goes to game page; track action uses `@click.stop` so it doesn’t navigate.

**Step 2: Write failing test – track from modal**

In `tests/Feature/GameSearchModalTest.php`, render the game-search-modal (e.g. via Livewire test helper with initial `query` that matches a game). As an authenticated user, call `trackGame($game->id)` and assert the user’s tracked games include that game.

**Step 3: Run test**

```bash
php artisan test --compact --filter=GameSearchModal
```

**Step 4: Write test – untrack from modal**

Same setup; attach the game to the user’s tracked list, call `untrackGame($game->id)`, assert the game is no longer in the user’s tracked games.

**Step 5: Run tests**

```bash
php artisan test --compact tests/Feature/GameSearchModalTest.php
```

**Step 6: Commit**

```bash
git add resources/views/components/⚡game-search-modal.blade.php tests/Feature/GameSearchModalTest.php
git commit -m "test: track/untrack from game search modal"
```

---

## Task 6: Optional – keyboard navigation in modal

**Files:**
- Modify: `resources/views/components/⚡game-search-modal.blade.php`

**Step 1: Design**

When the modal is open, Arrow Down/Up move a highlight among result items; Enter opens the highlighted game (same as clicking the row). Escape closes the modal (Flux default). Do not change Tab behavior for the search input and track buttons.

**Step 2: Implement with Alpine**

On the modal container or a wrapper, use Alpine: maintain `highlightedIndex` (0-based). On keydown (when focus is not in an input/textarea), if ArrowDown increment (cap at results length), if ArrowUp decrement (cap at 0); update highlight and scroll the highlighted item into view. On Enter, navigate to `route('games.show', $results[highlightedIndex])`. Ensure the list has appropriate `role` and `aria-selected` for the highlighted item for accessibility.

**Step 3: Commit (optional)**

```bash
git add resources/views/components/⚡game-search-modal.blade.php
git commit -m "feat: arrow-key navigation in game search modal"
```

---

## Summary

| Task | Description |
|------|-------------|
| 1 | Backdrop blur and responsive modal width (full mobile/tablet, ≥50% lg). |
| 2 | Dynamic search animations: staggered result list, optional loading state. |
| 3 | Glossy result rows: cover, title, subtitle, track action. |
| 4 | Keyboard shortcut ⌘K/Ctrl+K global, documented and tested. |
| 5 | Track/untrack from result with feature tests. |
| 6 | Optional: arrow-key navigation and Enter to open game. |

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-02-28-user-search-spotlight-raycast-modal.md`. Two execution options:

1. **Subagent-Driven (this session)** – Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run the plan task-by-task with checkpoints.

Which approach?
