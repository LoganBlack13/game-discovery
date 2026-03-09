# User Dashboard Full Rework — Gaming Tracker

> **For Claude:** Use the `executing-plans` skill to implement this plan task-by-task. Activate `frontend-design`, `livewire-development`, and `tailwindcss-development` when working on UI and components.

**Goal:** Fully rework the authenticated user dashboard so it delivers the product value described in the [homepage gaming tracker spec](2026-03-04-homepage-gaming-tracker-spec.md): track upcoming releases, follow news, plan backlog with estimated time, and see when the user will realistically play games—using **real user data** (tracked games, news, and—when available—backlog/time-to-beat). Best-in-class UX/UI per frontend-design guidelines. Laravel 12 + Livewire 4 + Tailwind v4 + DaisyUI 5.

**Scope:** User dashboard only (route `dashboard`). Homepage and admin are out of scope. Existing tests must pass or be updated; new behavior must be covered by tests.

---

## Design direction (frontend-design)

Before implementation, commit to a **bold aesthetic direction** so the dashboard feels intentional and memorable:

- **Purpose:** The dashboard is the user’s command center: “What’s coming? What’s in my backlog? When will I actually play this?”
- **Tone:** Choose one and stick to it—e.g. **refined/editorial** (clear hierarchy, confident typography, restrained motion) or **playful/confident** (strong color accents, card depth, subtle hover surprises). Avoid generic “dashboard gray.”
- **Differentiation:** One memorable element—e.g. a distinctive “next release” hero treatment, a backlog summary that feels like a progress bar, or a “playable date” insight that reads like a personal forecast.
- **Typography:** Reuse app fonts (Syne + DM Sans) for consistency; use weight and scale to create clear section hierarchy. No new font dependencies unless explicitly approved.
- **Motion:** Staggered section reveal on load (e.g. `animation-delay`), smooth transitions on filter/sort, and a polished game preview panel open/close. Prefer CSS-only where possible; Alpine for panel state.
- **Spatial composition:** Clear section order; avoid a single dense grid. Use full-width hero, then structured blocks (upcoming → backlog → news → playable insight → all games). Right sidebar for news stays; consider a compact “at a glance” strip for backlog total / next playable date.
- **Color & depth:** Use DaisyUI semantic tokens (`base-content`, `primary`, `base-200/300`) and theme variables. Add depth with subtle borders, card shadows, and gradient overlays on hero imagery—no flat gray blocks.

**Deliverable:** Before coding layout, document the chosen direction in a short “Dashboard design” note (can live in this plan or a comment in the layout) so all tasks align (e.g. “Refined editorial: clear sections, Syne for headings, DM Sans body, primary accent for CTAs, staggered fade-in per section”).

---

## Architecture

- **Route:** Keep `GET /dashboard` → `DashboardController` → `view('dashboard')`. Auth middleware unchanged.
- **Layout:** `resources/views/dashboard.blade.php` uses `x-layouts.app`; title e.g. “Dashboard” or “My games”.
- **Structure:** Single dashboard view that composes Livewire components. Section order:
  1. **Hero / Up next** — One “next release” hero + 3 following (existing concept, enhanced).
  2. **Upcoming releases** — Dedicated section: title, short description, 4–6 upcoming tracked games with countdown and news count; optional “Track more” CTA.
  3. **Backlog planning** — User’s tracked *released* games (or a “backlog” subset if we add status later) with estimated time-to-beat; total backlog hours; CTA “Plan your backlog” or “Add time estimates.”
  4. **Game news** — Right sidebar (existing) + optional inline “Latest for your games” strip or keep sidebar only.
  5. **When will you actually play it?** — Comparison/insight: e.g. table or cards showing “Game X → Release in N days → Backlog remaining Y hours → Estimated playable in Z.” Uses backlog total + release order to estimate “playable date.”
  6. **All tracked games** — Grid with sort/filter/platform (existing), possibly with game preview on card click.
- **Game preview panel:** Clicking a game card (upcoming or grid) opens a slide-over or modal with: cover, title, release date & countdown, estimated completion time (if `time_to_beat` exists, else “—”), latest news headline, “View game” link. Implement with Alpine + slot or small Livewire state (e.g. `selectedGameId` on parent or a dedicated wrapper component).
- **Tech stack:** Laravel 12, Livewire 4 (single-file components preferred), Tailwind v4, DaisyUI 5, Alpine.js (bundled with Livewire). No new npm/Composer dependencies unless approved.
- **Data:**
  - Upcoming: `auth()->user()->trackedGames()` with release_date > now, ordered by release_date, limit 6; `withCount('news')`.
  - Backlog: Tracked games that are “released” (or all tracked for V1) with optional `time_to_beat` (see below).
  - News: Existing `dashboard-news-sidebar` (tracked games’ news); optional reuse of `DashboardFeedService` for a “Recent updates” strip.
  - Playable date: Derived from backlog hours + release order; e.g. “If you have 90h backlog and play 10h/week, Game X (releases in 120d) is playable in ~3 weeks after release.”

---

## Backlog and time-to-beat

- **Option A (recommended for plan):** Add `time_to_beat` (nullable int, minutes or hours) to `games` table; user-editable on game show page or dashboard (later). Backlog section sums `time_to_beat` for “released” tracked games; “when will you play” uses this sum.
- **Option B:** No schema change in this plan. Backlog section uses **example/static** data (e.g. 3–4 fixed titles with hardcoded hours) or hides section until `time_to_beat` exists. Playable-date section uses placeholder text and example row.
- Plan should explicitly choose: e.g. “Phase 1: Backlog and playable-date with static/example data; Phase 2: migration + `time_to_beat` and real backlog math.” Implementation tasks below assume **Phase 1 = example/static** so the dashboard can ship without DB changes; add a follow-up task set for Phase 2 if desired.

---

## Key files

| Purpose | Path |
|--------|------|
| Dashboard view | `resources/views/dashboard.blade.php` |
| App layout | `resources/views/layouts/app.blade.php` |
| Up next + grid | `resources/views/components/⚡dashboard-game-list.blade.php` |
| News sidebar | `resources/views/components/⚡dashboard-news-sidebar.blade.php` |
| Dashboard feed (optional) | `resources/views/components/⚡dashboard-feed.blade.php` |
| Game card | `resources/views/components/game/card.blade.php` |
| Game preview panel | New: `resources/views/components/dashboard/game-preview-panel.blade.php` (Alpine or Livewire) |
| Backlog / playable logic | New or extend: `app/Services/DashboardBacklogService.php` (optional) or inline in Livewire |
| Routes | `routes/web.php` |
| Dashboard tests | `tests/Feature/DashboardTest.php` |

---

## Task breakdown

### Task 1: Design direction and dashboard shell

**Files:** `resources/views/dashboard.blade.php`, (optional) this plan or a short design note.

- Document the chosen aesthetic (refined editorial vs playful, one memorable element).
- Rework `dashboard.blade.php` into a clear section structure with semantic `<section>` and `aria-label`s:
  - Wrapper: hero + main two-column (content | sidebar).
  - Content column: Up next (hero + 3) → Upcoming releases block → Backlog planning block → When will you play block → All tracked games.
  - Sidebar: Latest news (existing component).
- Add section IDs where useful for deep-links (e.g. `id="upcoming"`, `id="backlog"`, `id="playable-insight"`).
- No change to auth or route; ensure existing `DashboardTest` still passes (adjust assertions only if copy/structure changed).

**Verify:** `php artisan test --compact tests/Feature/DashboardTest.php`

---

### Task 2: Up next hero and upcoming section (enhance)

**Files:** `resources/views/components/⚡dashboard-game-list.blade.php`, `resources/views/dashboard.blade.php`.

- **Hero:** Keep or enhance the current “Up next” hero (first upcoming game, full-width, countdown, cover). Apply design direction (typography, overlay, motion).
- **Upcoming releases:** Add a dedicated subsection “Upcoming releases” with:
  - Heading + short description (e.g. “Track the games you’re waiting for”).
  - Up to 6 upcoming tracked games (reuse or extend `getUpNextProperty()` / new computed property with `limit(6)` and `withCount('news')`).
  - Each item: cover, title, release date, countdown (existing script or Livewire), news count (e.g. “3 news”).
  - Use `x-game.card` or a dedicated `x-game.upcoming-card` with countdown + news count in a slot or subtitle.
- CTA under upcoming: “Track more games” → link to `/` or search.

**Verify:** Feature test that dashboard shows “Upcoming releases” and countdown/news count when user has upcoming tracked games; `php artisan test --compact tests/Feature/DashboardTest.php`.

---

### Task 3: Game preview panel (slide-over / modal)

**Files:** New `resources/views/components/dashboard/game-preview-panel.blade.php`; integrate in `⚡dashboard-game-list.blade.php` or dashboard view.

- Build a Blade component (Alpine-driven or Livewire) that:
  - Opens on game card click (from upcoming or “All tracked games” grid).
  - Shows: game cover, title, release date, countdown (if upcoming), estimated completion time (placeholder “—” until `time_to_beat`), latest news headline (one), “View game” link to `route('games.show', $game)`.
- Pass game data via Alpine `x-data` (e.g. JSON in `data-game` on card) or Livewire `wire:click` that sets `selectedGameId` and loads game in panel.
- Accessibility: focus trap, Escape to close, `aria-modal`/`role="dialog"`.

**Verify:** Test that dashboard renders cards and panel markup; optional Livewire test for “select game opens panel” (e.g. assert panel visible when `selectedGameId` set).

---

### Task 4: Backlog planning section (example/static data)

**Files:** `resources/views/dashboard.blade.php`, new Livewire component or inline in dashboard (e.g. `⚡dashboard-backlog.blade.php`).

- Add section “Plan your gaming backlog” with description “See how long your games take and estimate total time to complete your backlog.”
- **Phase 1:** Use example data: 3–4 games with hardcoded “estimated hours” (e.g. from config or a static list). If games exist in DB by slug/title, resolve and show cover/title; otherwise static labels + placeholder.
- Display: list or small cards (game, cover, estimated hours). Below: “Total backlog time: X hours.” CTA: “Plan your backlog” (link to profile/settings or placeholder).
- **Phase 2 (follow-up):** Add `time_to_beat` to `games` and a way for users to set it; replace example data with real tracked released games and sum.

**Verify:** Test that dashboard shows “Plan your gaming backlog” and “Total backlog time” and CTA; `php artisan test --compact tests/Feature/DashboardTest.php`.

---

### Task 5: When will you actually play it? (insight section)

**Files:** `resources/views/dashboard.blade.php` or `⚡dashboard-playable-insight.blade.php`.

- Add section “When will you actually play it?” with description “Your backlog determines when you’ll start new games.”
- **Phase 1:** Static example: comparison table (columns: Game, Release date, Backlog remaining, Estimated playable date). One or two example rows (e.g. “Silksong — Release in 120 days — Backlog 90h — Playable in ~2 weeks after release”). Use DaisyUI table or card-based layout.
- **Phase 2 (follow-up):** Drive from real backlog total + release dates and a simple model (e.g. fixed hours per week) to compute “playable in X weeks after release.”

**Verify:** Test that dashboard shows section title, description, and example table/row; `php artisan test --compact tests/Feature/DashboardTest.php`.

---

### Task 6: News sidebar and feed (retain / refine)

**Files:** `resources/views/components/⚡dashboard-news-sidebar.blade.php`, optionally `resources/views/dashboard.blade.php`.

- Keep “Latest news” in the right sidebar; ensure it still shows only news for tracked games and infinite scroll.
- Optionally add an inline “Latest for your games” strip in the main column (limit 4–5 items) using existing `DashboardFeedService` or direct News query; if omitted, sidebar is the single news surface.
- Align styling with design direction (card style, typography).

**Verify:** Existing tests for news sidebar and feed (if any) still pass; `php artisan test --compact tests/Feature/DashboardTest.php`.

---

### Task 7: All tracked games grid and filters

**Files:** `resources/views/components/⚡dashboard-game-list.blade.php`.

- Keep “All tracked games” grid (5 columns on xl), sort/filter/platform dropdowns. Ensure each card can open the game preview panel (Task 3).
- Apply design direction (spacing, cards, hover). Preserve existing behavior (filter released/upcoming/no_date, sort by release_date/recently_added/alphabetical/countdown).

**Verify:** `php artisan test --compact tests/Feature/DashboardTest.php` (guest redirect, only tracked games, hero/up next, news sidebar, feed filters).

---

### Task 8: Responsive layout and motion

**Files:** `resources/views/dashboard.blade.php`, `resources/views/components/⚡dashboard-game-list.blade.php`, `resources/views/components/⚡dashboard-news-sidebar.blade.php`, CSS (Tailwind).

- Ensure responsive behavior: on small screens, sections stack (e.g. hero → upcoming → backlog → playable insight → news → tracked games); on lg, two-column (content | sidebar). Use Tailwind breakpoints and gap utilities.
- Add staggered reveal on load (e.g. `animation-delay` per section or `@keyframes` fade-in) and smooth transitions for panel open/close and filter changes. Prefer CSS; use `wire:transition` or Alpine only if needed.

**Verify:** Manual check and/or browser test; no regression in feature tests.

---

### Task 9: Tests and copy

**Files:** `tests/Feature/DashboardTest.php`, dashboard view and components.

- Update or add tests for: new section headings (“Upcoming releases,” “Plan your gaming backlog,” “When will you actually play it?”), backlog total and CTA, playable-insight content, game preview panel (presence of panel in DOM or open state).
- Remove or update assertions that conflict with new copy (e.g. “My tracked games” vs “Dashboard”).
- Ensure guest redirect, “only tracked games,” hero, news sidebar, and feed filter tests still pass.

**Verify:** `php artisan test --compact tests/Feature/DashboardTest.php`; optionally `tests/Browser/` if present.

---

### Task 10: Pint and final smoke test

**Files:** N/A (commands).

- Run `vendor/bin/pint --dirty` (or `--format agent` per project rules).
- Run full dashboard and welcome test set; fix any failures.

**Verify:** All targeted tests pass; code style consistent.

---

## Out of scope / assumptions

- **Homepage:** Spec in `2026-03-04-homepage-gaming-tracker-spec.md` remains the source of *feature ideas*; implementation in this plan is for the **user dashboard** only. Homepage rework is separate.
- **Authentication:** Existing auth and middleware unchanged; dashboard remains behind `auth` + `verified`.
- **time_to_beat:** Phase 1 uses example/static backlog and playable date; Phase 2 can add migration and real backlog logic in a follow-up plan.
- **Admin:** No changes to admin dashboard or layout.
- **New dependencies:** None unless explicitly approved.

---

## Summary

| # | Task | Main deliverable |
|---|------|------------------|
| 1 | Design direction and dashboard shell | Section structure, aria-labels, section IDs |
| 2 | Up next hero + Upcoming releases | 6 upcoming with countdown + news count, CTA |
| 3 | Game preview panel | Slide-over/modal on card click with release, time, news, link |
| 4 | Backlog planning | Section with example data, total hours, CTA |
| 5 | When will you play it? | Section with example table/cards |
| 6 | News sidebar (retain/refine) | Sidebar + optional strip, styling |
| 7 | All tracked games grid | Grid + filters + panel trigger |
| 8 | Responsive + motion | Breakpoints, stagger, transitions |
| 9 | Tests and copy | Updated DashboardTest, new assertions |
| 10 | Pint + smoke test | All tests pass, Pint clean |

Implement in order; each task ends with the specified verification step.
