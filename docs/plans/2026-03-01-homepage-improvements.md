# Homepage Improvements

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** (1) Show **one line per category** (single horizontal row of game cards per section). (2) In the "Coming soon" category, place **games with no release date at the end**. (3) Add a **news card** section showing the **latest 10 news** with **infinite scroll**. (4) Apply **frontend-design skill** guidelines to improve homepage visual (typography, color, motion, spatial composition, backgrounds) so the page is distinctive and production-grade.

**Architecture:** Welcome page stays as a single Livewire component (`pages::welcome`). Game sections (Coming soon, Trending now, Recently released) each render a single horizontal row (no grid wrap on desktop); mobile keeps horizontal scroll. Coming soon query: order by release_date ASC with nulls last (e.g. `orderByRaw` + `orderBy('release_date')`). New "Latest news" section: dedicated Livewire component or inline logic for News (latest 10, then load more via infinite scroll using Alpine `x-intersect` + `$wire.loadMore()`). Visual improvements follow frontend-design skill: bold aesthetic direction, typography, motion (staggered reveals, scroll/hover), spatial composition, and cohesive color/backgrounds.

**Tech stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Alpine (for intersect). No new dependencies.

**Key files:**
- [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php) – layout, sections, one row per category, news section
- [app/Models/Game.php](app/Models/Game.php) – optional new scope or query for upcoming with nulls last
- New or inline: homepage news list (10 + infinite scroll); can be a Blade component + Livewire fragment or a separate Livewire component
- [resources/css/app.css](resources/css/app.css) – any new keyframes or theme tweaks for homepage
- [tests/Feature/WelcomePageTest.php](tests/Feature/WelcomePageTest.php) – update/add tests for ordering and news

---

## Out of scope / assumptions

- Homepage news shows **all** latest news (not filtered by user tracked games); no auth required. Reuse `News` model and `game` relation.
- "One line per category" means one visible row of cards; horizontal scroll on small screens is acceptable. Exact number of cards per row can be fixed (e.g. 4–6) or responsive (e.g. 4 on lg, scroll on smaller).
- Frontend-design improvements stay within existing stack (Tailwind v4, Flux, no new CSS framework). Match existing dark mode and existing `font-display` / `font-sans` where the skill suggests refinement rather than replacement.

---

## Task 1: One line per category

**Files:** [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php)

- **Coming soon, Trending now, Recently released:** Change each section from a responsive grid (`sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4`) to a **single horizontal row** on all breakpoints.
- Use a flex row with horizontal scroll on small screens and a fixed number of visible cards on large (e.g. `flex gap-4 overflow-x-auto` with `sm:overflow-visible` and a single row so items don’t wrap — e.g. `flex-nowrap` and optionally `lg:grid lg:grid-cols-4` with one row only by limiting items, or keep `flex` and allow scroll when many items). Ensure only one row is shown (no wrap).
- **Concrete layout:** One row of cards per section; on mobile: horizontal scroll; on desktop: either same scroll or a fixed number per row (e.g. 4 or 6) in one row. Remove any grid that creates multiple rows (e.g. 2x6 or 3x4). Limit remains 12 per category unless product asks to change.

---

## Task 2: Coming soon — games with no release date at the end

**Files:** [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php), [app/Models/Game.php](app/Models/Game.php) (optional)

- **Requirement:** In "Coming soon", games that have a `release_date` must appear first (ordered by date ascending); games with **no release date** (`release_date` null) must appear **at the end**.
- **Implementation:** In `getUpcomingGames()`, replace `->byReleaseDate()` with an ordering that puts nulls last. Options:
  - **Option A (recommended):** Add a scope on `Game`, e.g. `scopeUpcomingByReleaseDate(Builder $query): Builder` that does `orderByRaw('release_date IS NULL')->orderBy('release_date')` (MySQL/SQLite: nulls last for ASC). Use it in `getUpcomingGames()`.
  - **Option B:** Inline in welcome: `->orderByRaw('release_date IS NULL')->orderBy('release_date')` (or equivalent for the project’s DB driver).
- Ensure `getUpcomingGames()` still uses `->upcoming()` and `->limit(12)`.

---

## Task 3: Latest news card (10 items + infinite scroll)

**Files:** New Livewire component (e.g. `resources/views/components/⚡welcome-news.blade.php`) or inline in welcome; [routes/web.php](routes/web.php) unchanged (welcome already handles `/`).

- **Data:** Query `News::query()->with('game')->orderByDesc('published_at')`. Paginate: first page 10 items; expose `loadMore()` that appends next 10 (same pattern as dashboard news sidebar: `$page`, `$perPage = 10`, `getItemsProperty()` returning `['items' => ..., 'hasMore' => ...]`).
- **Placement:** Add a "Latest news" section on the welcome page (e.g. after "Recently released" or between hero and game sections — specify in implementation). Section heading e.g. "Latest news".
- **UI:** Card-style list: each item shows news title (link to `url`, `target="_blank"` `rel="noopener noreferrer"`), optional thumbnail, game name (link to game show), published date. Use `title` attribute for full headline when truncated.
- **Infinite scroll:** Use Alpine `x-intersect` on a sentinel element at the bottom of the list: when it enters view, call `$wire.loadMore()`. Reuse the pattern from [resources/views/components/⚡dashboard-news-sidebar.blade.php](resources/views/components/⚡dashboard-news-sidebar.blade.php) (`x-intersect.once` per load to avoid duplicate calls; note: "once" may only fire once per element — if the sentinel is replaced or multiple pages are loaded, ensure loadMore is called for each next page, e.g. by re-triggering intersect when new items are appended or using a non-once listener that fires when the sentinel is visible and hasMore is true).
- **Component:** Prefer a dedicated Livewire component (e.g. `<livewire:welcome-news />`) included in the welcome view so state (page, items) is isolated and testable. Component returns only the news section markup.

---

## Task 4: Visual improvements (frontend-design skill)

**Files:** [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php), [resources/css/app.css](resources/css/app.css) (if new animations or theme), and the new welcome-news component view.

Apply the **frontend-design skill** guidelines:

- **Design direction:** Choose one clear direction (e.g. refined minimal with strong typography and subtle motion, or editorial with asymmetric layout and bold accents). Document the choice briefly in the plan or in a short comment in code. Avoid generic AI aesthetics (e.g. default purple gradients, Inter/Roboto).
- **Typography:** Ensure display and body fonts are used consistently; consider one distinctive tweak (e.g. section labels in a different weight or letter-spacing) that fits the app’s existing `font-display` / `font-sans` (Syne, DM Sans).
- **Color & theme:** Cohesive palette; use CSS variables / Tailwind theme if needed. Ensure dark mode is respected (`dark:`). Accent (e.g. cyan) used intentionally for CTAs and section labels.
- **Motion:** Staggered reveals on load are already present (`welcome-animate`, `welcome-animate-delay-*`). Consider one high-impact moment (e.g. section scroll-in or card hover) that fits the chosen direction. Prefer CSS-only where possible.
- **Spatial composition:** Consider asymmetry or one grid-breaking element (e.g. news card width or placement) so the layout feels intentional. Use gap utilities; avoid extra margins between sections.
- **Backgrounds & depth:** Existing gradient mesh and grain are good. Optionally add subtle depth (e.g. card shadows, borders) to the new news card section so it feels integrated.

Do not redesign the entire site; scope is the **homepage only**. Match existing conventions (Flux components, Tailwind, dark mode) while elevating the page within those constraints.

---

## Task 5: Tests

**Files:** [tests/Feature/WelcomePageTest.php](tests/Feature/WelcomePageTest.php)

- **Coming soon order:** Add a test that creates two upcoming games: one with `release_date` set (e.g. next month) and one with `release_date` null. Assert that the game with a date appears before the game with null on the welcome page (e.g. by checking order in the HTML or that the first "Coming soon" card has a date and a later card has no date or "TBA").
- **News section:** Add a test that the welcome page shows a "Latest news" (or equivalent) section and that when News exists, at least one news title or link is present. Optionally test that the news component shows up to 10 items initially (create 15 news, assert 10 are shown or that "Load more" / sentinel exists).
- Keep existing tests passing: "welcome page loads and shows hero content", "welcome page shows real upcoming popular and recently released games". Update any assertions that break when changing section structure (e.g. "Trending now" still present).

---

## Task 6: Pint and polish

- Run `vendor/bin/pint --dirty` on touched PHP.
- Ensure no linter errors on modified files.
- Verify welcome page and news section work in both light and dark mode.

---

## Summary

| Step | Action |
|------|--------|
| 1 | One line per category: single row per section (Coming soon, Trending now, Recently released); horizontal scroll on small screens |
| 2 | Coming soon: order by release_date ASC with games with no release date at the end (scope or inline orderByRaw) |
| 3 | Latest news card: 10 items, infinite scroll (Livewire component or inline), link to article and game, new tab and tooltip |
| 4 | Visual improvements per frontend-design skill (typography, color, motion, spatial composition, backgrounds) on homepage only |
| 5 | Tests: coming soon ordering, news section presence and optional pagination |
| 6 | Pint and polish |

---

## Notes

- **Nulls last:** MySQL and SQLite: `ORDER BY release_date IS NULL, release_date ASC` puts nulls last. PostgreSQL has `NULLS LAST`. Use Laravel’s `orderByRaw('release_date IS NULL')` then `orderBy('release_date')` for portability.
- **Infinite scroll sentinel:** If using `x-intersect.once`, the "once" applies to the element; when new items are appended, the sentinel moves down and can intersect again, so `loadMore` can be called for each page. If the sentinel is removed when there are no more items (`hasMore` false), no further calls occur. Test with 15+ news items to confirm second page loads.
- **News for guests:** Homepage is public; news list is not filtered by user. Reuse `News::query()->with('game')->orderByDesc('published_at')` in the welcome news component.
