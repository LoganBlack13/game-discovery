# Homepage Full Rework Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Completely reimagine the user-facing homepage into a bold, memorable “Signal Grid Arcade” style control center for game discovery and tracking, optimized for desktop and mobile, with first-class DaisyUI 5 theming (including theme switching) and Livewire-powered personalization.

**Architecture:** Replace the current simple carousels with a structured set of Livewire components composed into the existing `⚡welcome` page, organized around a “Tonight’s Picks” hero, mood-based discovery, backlog and platform signals, social signals, and news/events. Introduce a centralized DaisyUI 5 theme configuration and a theme switcher that uses semantic colors (base/primary/secondary/accent, etc.) so the entire homepage (and later the app) can safely support at least 5 distinct visual themes without breaking text contrast or readability. All UI building blocks should be expressed as reusable Blade and/or Livewire components so other pages can reuse the new design language.

**Tech Stack:** Laravel 12, Livewire 4, Blade, Tailwind CSS 4, DaisyUI 5, Alpine (for light interactions where appropriate), Pest for tests.

---

## Aesthetic Direction & UX Concept

### Task 0: Lock in “Signal Grid Arcade” direction

**Files:**
- Reference only (no code yet) – use this section as conceptual guide for implementation tasks.

**Steps:**
1. Decide and document that the homepage aesthetic direction is **“Signal Grid Arcade”**:
   - A game-OS style control center that surfaces “signals” about what to play next: Tonight’s Picks, Moods, Backlog Heat, Platform spotlights, Friends’ activity, and News/Events.
2. In this plan and in short code comments where appropriate (e.g. Livewire class PHPDoc), explicitly refer to this direction so future contributors understand the intention.
3. Ensure every visual decision in subsequent tasks supports:
   - Fast scanning (2–3 seconds to answer “What should I play tonight?”).
   - A sense of live, personalized “control center” rather than a static content page.
   - Clean layout hierarchy: one hero band + supporting signal sections.

---

### Task 0b: Data & dependency reality check

**Files:**
- Reference only (update this plan if needed; no code required).

**Step 1: Inventory existing data and features**
- For each section:
  - Tonight’s Picks (recommendations from tracked games / popularity)
  - Mood-based discovery (genres/tags on games)
  - Backlog Explorer (tracked games with status + hours)
  - Platform Spotlights (games by platform and subscription state)
  - Friends Activity (social graph, presence, recent activity)
  - News/Guides/Events (existing `News` model and feeds)
- Document in this plan (or separate notes) whether:
  - Data & relationships exist and are production-ready.
  - Only partial data exists.
  - Feature is effectively greenfield.

**Step 2: Decide V1 vs later iterations**
- Explicitly mark for this iteration (a few days of work by one engineer):
  - **In-scope (real data)**: Hero/Tonight’s Picks (based on tracked + popular games), Mood chooser (simple genre tags), Backlog Explorer (where tracking exists), Platform Spotlights (basic per-platform rails), News Feed (using existing `News` model and latest items).
  - **Stubbed but visually present**: Friends Activity (placeholder copy and CTAs, minimal or mocked data).
  - **Out-of-scope for V1**: Any complex recommendation or social logic that would require substantial backend or data pipeline work beyond what exists now.

**Step 3: Adjust later tasks as needed**
- Based on the inventory, tweak the implementation details of Tasks 6–11 so they match what’s realistically available (e.g. simply highlight “popular among all users” rather than “friends playing now” if that signal doesn’t exist yet).

---

## Information Architecture & Component Tree

### Task 1: Define homepage component and section structure

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Create or modify: `app/Livewire/Home/Welcome.php` (page orchestrator Livewire component)
- Create: `app/Livewire/Home/HeroTonightPicks.php`
- Create: `app/Livewire/Home/MoodChooser.php`
- Create: `app/Livewire/Home/BacklogExplorer.php`
- Create: `app/Livewire/Home/PlatformSpotlights.php`
- Create: `app/Livewire/Home/FriendsActivity.php`
- Create: `app/Livewire/Home/NewsFeed.php`
- Create: `resources/views/livewire/home/hero-tonight-picks.blade.php`
- Create: `resources/views/livewire/home/mood-chooser.blade.php`
- Create: `resources/views/livewire/home/backlog-explorer.blade.php`
- Create: `resources/views/livewire/home/platform-spotlights.blade.php`
- Create: `resources/views/livewire/home/friends-activity.blade.php`
- Create: `resources/views/livewire/home/news-feed.blade.php`

**Step 1: Wire homepage route, layout, and orchestrator**
- In `routes/web.php`, confirm which route renders the welcome page (likely `Route::get('/', ...)`) and ensure it points to the Livewire/Blade welcome setup used here; note any differences in this plan if needed.
- Confirm which layout Blade file (`resources/views/layouts/app.blade.php` or similar) wraps `⚡welcome` so that header/nav/theme switcher integration is clear.
- Create or update `app/Livewire/Home/Welcome.php` to act as the **page orchestrator**:
  - Owns global homepage state (selected mood, platform, session length, and any shared filters).
  - Performs primary Eloquent queries with eager loading and simple caching where appropriate.
  - Exposes pre-loaded collections/DTOs as public properties to pass down to child components.
- Ensure `resources/views/pages/⚡welcome.blade.php` renders the `Welcome` Livewire component (e.g. `<livewire:home.welcome />`) from within the layout.

**Step 2: Write the “wireframe” in the `Welcome` Livewire view**
- Create `resources/views/livewire/home/welcome.blade.php` to define the section structure:
  - Keeps the top-level `<div>` structure consistent with the existing page/layout.
  - Introduces clearly labeled sections in order, passing data from orchestrator properties:
    1. `hero` – `<livewire:home.hero-tonight-picks :games="$tonightPicks" :mood="$mood" :platform="$platform" :sessionLength="$sessionLength" />`
    2. `mood` – `<livewire:home.mood-chooser :mood="$mood" />`
    3. `continue-or-start-fresh` – part of Hero or dedicated area inside `hero` component.
    4. `backlog-explorer` – `<livewire:home.backlog-explorer :backlogGames="$backlogGames" />`
    5. `platform-spotlights` – `<livewire:home.platform-spotlights :platformGames="$platformGames" />`
    6. `friends-activity` – `<livewire:home.friends-activity :friendSignals="$friendSignals" />` (can be stubbed for V1)
    7. `news-feed` – `<livewire:home.news-feed :newsItems="$newsItems" />`
- Use semantic section headings and DaisyUI sections (e.g. `section` + `max-w-7xl mx-auto px-4`) but keep them visually minimal initially.

**Step 2: Create Livewire classes (skeletons)**
- Use `php artisan make:livewire Home/HeroTonightPicks --no-interaction`, etc., for each component listed above.
- In each component class, define minimal public properties and `render()` method returning the corresponding view.

**Step 3: Run tests / manual check**
- Run: `php artisan test --compact tests/Feature/WelcomePageTest.php` (or existing equivalent).
- Manually hit the homepage route to verify all components mount without runtime errors (even if content is placeholder).

---

## Reusable UI Building Blocks

### Task 2: Create reusable Blade components for cards and signals

**Files:**
- Create: `resources/views/components/game/card.blade.php`
- Create: `resources/views/components/game/hero-tile.blade.php`
- Create: `resources/views/components/ui/signal-card.blade.php`
- Create: `resources/views/components/ui/section-header.blade.php`

**Step 1: Game card**
- Implement `game/card` Blade component that accepts:
  - Props: `game`, `status`, `tags` (array), `variant` (`default`, `compact`, `list`).
  - Layout using DaisyUI `card` classes, `bg-base-200` / `bg-base-300` and `text-base-content`.
- Include:
  - Cover art, title, platform badges (DaisyUI `badge`), 1–2 tags, small progress indicator (optional).
  - CTA button (`btn btn-primary btn-xs` or `btn-outline`) slot or prop.

**Step 2: Hero tile**
- Implement `game/hero-tile` component:
  - Larger layout: background image area, gradient overlay, big title, short tagline, reasons (badges).
  - Primary CTA (`btn-primary`) and secondary CTA (`btn-ghost`).

**Step 3: Signal card**
- Implement `ui/signal-card` component:
  - Compact display for Now Playing, Backlog Heat, Activity Pulse.
  - Props: `icon`, `label`, `value`, `tone` (`info`, `success`, `warning`, `error`, `neutral`).
  - Uses DaisyUI `card` or simple `div` with `bg-base-200` and state-colored border.

**Step 4: Section header**
- Implement `ui/section-header` component:
  - Props: `title`, `subtitle`, optional right-aligned actions slot.
  - Uses consistent typography and spacing for all homepage sections.

**Step 5: Wire components into one Livewire view**
- Update one Livewire view (e.g. `hero-tonight-picks`) to use these components and ensure they render correctly with stub data.

**Step 6: Tests**
- Add/extend a small feature test to assert that the homepage HTML contains elements from `game-card` and `hero-tile` (e.g. known heading text or CSS classes).

---

## DaisyUI 5 Configuration & Theming

### Task 3: Configure DaisyUI 5 and Tailwind v4 for semantic theming

**Files:**
- Modify: `tailwind.config.js` or Tailwind 4 config entry point (if present; otherwise confirm project-specific Tailwind 4 config file).
- Modify: `resources/css/app.css`

**Step 1: Locate existing Tailwind/daisyUI config**
- Open the Tailwind configuration (for Tailwind v4 it may be minimal and CSS-first).
- Identify how DaisyUI is currently included (plugin or CSS import).

**Step 2: Ensure DaisyUI 5 is configured with semantic themes**
- In `tailwind.config.js` (or equivalent), configure DaisyUI plugin with:
  - `themes` array with at least 5 named themes (see Task 4).
  - Ensure DaisyUI uses semantic color tokens (`primary`, `secondary`, `accent`, `neutral`, `info`, `success`, `warning`, `error`, `base-100`, `base-200`, `base-300`, `base-content`, etc.).
- Confirm that no components rely on hardcoded hex colors where DaisyUI semantic colors should be used.

**Step 3: Verify Tailwind v4 CSS import**
- In `resources/css/app.css`, confirm Tailwind v4 style import:
  - Use `@import "tailwindcss";` and any `@theme` blocks for custom design tokens.
- Ensure any custom colors used for homepage align with DaisyUI tokens, not bespoke hex values in arbitrary classes.

**Step 4: Quick smoke test**
- Run: `bun run build` or `bun run dev` (as appropriate for the project) to ensure Tailwind+daisyUI build still succeeds.

---

### Task 4: Define at least 5 DaisyUI-compatible themes

**Files:**
- Modify: Tailwind/DaisyUI config (same as Task 3)

**Step 1: Design 5 theme concepts**
- Define five named DaisyUI themes that fit the app:
  1. `arcade-night` – neon accents on dark base, for the primary “Signal Grid Arcade” feel.
  2. `daylight-pastel` – soft light theme with gentle saturation for daytime browsing.
  3. `retro-crt` – amber/green highlights with darker base for retro vibe.
  4. `noir-minimal` – high-contrast, monochrome-leaning theme with minimal color pops.
  5. `cosmic-fade` – deep blues/purples with subtle gradients, but still semantically mapped.

**Step 2: Implement themes using DaisyUI semantic colors**
- For each theme, provide:
  - `primary`, `secondary`, `accent`, `neutral`
  - `base-100`, `base-200`, `base-300`, `base-content`
  - State colors: `info`, `success`, `warning`, `error`
- Use DaisyUI best practices:
  - Ensure `*-content` variants have proper contrast with their corresponding base/background.
  - Avoid hardcoded text colors (e.g. `text-white`) on elements that should derive from `base-content` or `primary-content`.

**Step 3: Verify contrast and legibility**
- Use DaisyUI documentation guidance and your own visual judgment:
  - For each theme, check that body text, buttons, and cards are readable.
  - Adjust `base-content` or state colors if contrast is too low.

**Step 4: Tests**
- Add a small Pest test (can be in a general UI/theme test file) that:
  - Renders a simple page or component with `data-theme` set to each of the five themes via Blade.
  - Asserts that at least one theme-specific class or expected attribute appears, proving themes are wired up.

---

### Task 5: Implement a global theme switcher (desktop + mobile)

**Files:**
- Create: `app/Livewire/Theme/ThemeSwitcher.php`
- Create: `resources/views/livewire/theme/theme-switcher.blade.php`
- Modify: main layout Blade file (e.g. `resources/views/layouts/app.blade.php` or equivalent) to include the theme switcher and bind `data-theme`.
- Optional: Add small helper in `app/Support` or similar for theme persistence.

**Step 1: Design theme switcher UI**
- Use DaisyUI `theme-controller` / `dropdown` + icons:
  - On desktop: small control in top-right app bar (icon + theme name).
  - On mobile: accessible from main nav or a floating button.

**Step 2: Implement Livewire theme switcher**
- In `ThemeSwitcher` component:
  - Public property `public string $theme` with default theme (`arcade-night`).
  - Method `setTheme(string $theme)` with validation against allowed themes.
  - Persist chosen theme to session and optional user preference (if authenticated).

**Step 3: Bind `data-theme` on `<html>` or `<body>`**
- In the main layout Blade:
  - Read current theme (session/user) via a helper or Livewire parent.
  - Set `data-theme="{{ $currentTheme }}"` on the root element so DaisyUI applies that theme.

**Step 4: Wire theme switcher into layout**
- Include `<livewire:theme.theme-switcher />` in layout header/nav.
- Ensure it uses DaisyUI `theme-controller` patterns and semantic theme names from Task 4.

**Step 5: Tests**
- Add Pest feature tests:
  - Hitting homepage with default session shows default theme on root `data-theme`.
  - Posting/triggering theme switch Livewire action sets session and results in changed `data-theme` on subsequent requests.

---

## Hero: “Tonight’s Picks” & Above-the-Fold

### Task 6: Implement “Tonight’s Picks” hero band

**Files:**
- Modify: `app/Livewire/Home/HeroTonightPicks.php`
- Modify: `resources/views/livewire/home/hero-tonight-picks.blade.php`
- Reuse: `resources/views/components/game/hero-tile.blade.php`
- Reuse: `resources/views/components/ui/signal-card.blade.php`

**Step 1: Define data contract**
- In `HeroTonightPicks`:
  - Public properties: `$mood`, `$platform`, `$sessionLength`, `$games` (collection).
  - Computed/queried recommendations based on:
    - User-tracked games (if authenticated).
    - Fallback: popular + upcoming games for guests.

**Step 2: Layout the hero**
- In the view:
  - Left 2/3: greetings + filter chips + 3–4 hero tiles in a horizontal rail.
  - Right 1/3: column of `signal-card`s for Now Playing, Backlog Heat, Activity Pulse (can be stubbed initially).
- Use DaisyUI `card`, `badge`, `btn`, `tabs` and Tailwind layout utilities.

**Step 3: Interaction**
- Make mood/platform/session length chips clickable:
  - Wire to Livewire actions that update properties and recompute `$games`.
  - Add subtle motion: fade/translate hero tiles when filters change.

**Step 4: Tests**
- Pest feature tests:
  - Unauthenticated user sees hero with at least one recommended game tile.
  - If user has tracked games (seeded in test), hero pulls from those titles.

---

### Task 7: Implement “Choose by Mood” section

**Files:**
- Modify: `app/Livewire/Home/MoodChooser.php`
- Modify: `resources/views/livewire/home/mood-chooser.blade.php`
- Reuse: `resources/views/components/game/card.blade.php`
- Reuse: `resources/views/components/ui/section-header.blade.php`

**Step 1: Mood tabs/chips**
- Create a set of moods (e.g. Cozy, Story-heavy, Roguelike, Competitive, Social, Retro).
- Display them as DaisyUI `tabs` or `badge`-like chips in a horizontally scrollable row.

**Step 2: Mood-based recommendations**
- In `MoodChooser`, implement query logic that:
  - Uses tags/genres from the `Game` model (or temporary stub dataset) to filter games by mood.
  - Returns a small grid (e.g. 3×2 on desktop, single column on mobile) of `game/card` components.

**Step 3: “Add to Tonight’s Queue” CTA**
- Each card has an “Add to Tonight’s Picks” button:
  - Emit Livewire event or call parent (if using nested components) to update hero’s selection.

**Step 4: Tests**
- Pest feature test to ensure:
  - When a mood is selected, the section shows mood-specific games (e.g. with a `data-mood` attribute or known tag in HTML).

---

## Deeper Discovery Sections

### Task 8: Backlog Explorer

**Files:**
- Modify: `app/Livewire/Home/BacklogExplorer.php`
- Modify: `resources/views/livewire/home/backlog-explorer.blade.php`
- Reuse: `resources/views/components/game/card.blade.php`

**Step 1: Filters**
- Provide filters for:
  - Platform, Time to Finish, Status (Backlog, Completed, Wishlist), Genre.
- Use DaisyUI `select` or pill-like controls in a compact filter bar.

**Step 2: Grid layout**
- Implement a dense grid with:
  - 3–5 columns on desktop, 1–2 on mobile.
  - Each card shows title, estimate of hours remaining, status, and a subtle priority `progress` bar.

**Step 3: Tests**
- Feature test that:
  - When filtering by a platform or status, only matching games are rendered.

---

### Task 9: Platform Spotlights

**Files:**
- Modify: `app/Livewire/Home/PlatformSpotlights.php`
- Modify: `resources/views/livewire/home/platform-spotlights.blade.php`
- Reuse: `resources/views/components/game/card.blade.php`
- Reuse: `resources/views/components/ui/section-header.blade.php`

**Step 1: Per-platform rails**
- For key platforms (e.g. Steam, Xbox, PlayStation, Switch, Game Pass):
  - Render a narrow horizontal rail (like existing carousels, but with enhanced styling).
  - Each rail has filters: “New this week”, “On sale”, “Leaving soon” (stubbed or gradually implemented).

**Step 2: Tests**
- Feature test that ensures the section renders a rail per platform when games exist for that platform.

---

### Task 10: Friends Activity (stubbed, but visually designed)

**Files:**
- Modify: `app/Livewire/Home/FriendsActivity.php`
- Modify: `resources/views/livewire/home/friends-activity.blade.php`
- Reuse: `resources/views/components/ui/signal-card.blade.php`

**Step 1: Visual stub**
- If the project doesn’t yet have rich social data, implement:
  - A visually complete section with placeholder or minimal real data (e.g. fake friends or “Connect accounts” CTA).

**Step 2: Layout**
- Grid of signal cards indicating:
  - “X friends playing [Game]”
  - “Y friends returned to [Game] this week”

**Step 3: Tests**
- Feature test that the section renders a placeholder state and a CTA when social data is unavailable.

---

### Task 11: News, Guides & Events

**Files:**
- Modify: `app/Livewire/Home/NewsFeed.php`
- Modify: `resources/views/livewire/home/news-feed.blade.php`
- Reuse: `resources/views/components/ui/section-header.blade.php`
- Reuse or replace: existing `livewire:welcome-news` logic (migrate/integrate if appropriate).

**Step 1: Data model**
- Use existing `News` model and relationships:
  - Query latest news with `with('game')` as appropriate.
  - Provide filters such as “From your library” vs “All news” (if user is logged in).

**Step 2: Layout**
- Top: 1–2 larger editorial cards for major updates/events.
- Below: compact list of other news, with:
  - Titles, game names, source, date, and tags.
  - Links open in new tab with proper `rel` attributes.

**Step 3: Infinite scroll / pagination (optional extended)**
- Reuse or extend existing infinite scroll approach:
  - Use a Livewire property like `$page` and sentinel element with `x-intersect` to load more.

**Step 4: Tests**
- Feature tests to verify:
  - News section appears on homepage.
  - At least one news item is rendered when data exists.
  - “From your library” filter (if implemented) biases content to user games.

---

## Motion, Layout Polish & Responsiveness

### Task 12: Motion & hover interactions

**Files:**
- Modify: `resources/css/app.css`
- Modify: various Livewire views (`hero-tonight-picks`, `mood-chooser`, `backlog-explorer`, etc.)

**Step 1: Define animation utilities**
- Add keyframes and utility classes for:
  - Subtle card lift on hover.
  - Fade+slide in for sections when they first scroll into view.

**Step 2: Apply motion**
- Use new utilities:
  - On hero tiles, apply hover scale and shadow.
  - On section wrappers, apply entrance animations (but keep them performant).

**Step 3: Tests**
- Smoke test: ensure CSS builds and no class name collisions occur.

---

### Task 13: Desktop & mobile layout refinement

**Files:**
- Modify: all homepage Livewire views to refine responsive breakpoints.

**Step 1: Desktop**
- Ensure:
  - Hero uses `grid` or `flex` with thoughtful gaps.
  - Secondary sections (Backlog, Platforms, News) align to common columns.

**Step 2: Mobile**
- Ensure:
  - Sections stack vertically with clear hierarchy.
  - Game cards convert to list-style variant (`variant="list"` on `game/card`).
  - Controls are thumb-friendly with sufficient padding.

**Step 3: Tests**
- Consider a browser/Pest browser test (if project is set up for it) to assert:
  - No horizontal scrollbars at common mobile widths (except deliberate rails).
  - Key content is visible without zooming.

---

## Testing, Quality & Tooling

### Task 14: Update or add homepage feature tests

**Files:**
- Modify/Create: `tests/Feature/HomepageFullReworkTest.php` (or extend existing `WelcomePageTest`)

**Step 1: Basic render tests**
- Assert homepage loads successfully.
- Assert presence of key sections (hero, mood, backlog, platforms, news).

**Step 2: Interaction tests (Livewire)**
- Use Pest + Livewire testing:
  - Mood selection changes rendered content.
  - Backlog filters narrow results.
  - Theme switcher persists chosen theme across requests.

---

### Task 15: Pint, lint, and visual review

**Files:**
- All modified PHP and Blade files.

**Step 1: Code style**
- Run: `vendor/bin/pint --dirty`.

**Step 2: Lints**
- Run linter or static analysis if configured.

**Step 3: Visual QA**
- Manually test:
  - Each of the 5 themes on desktop and mobile widths.
  - Dark/light experiences feel coherent, with no illegible text.

---

## Execution Notes

- Implement tasks incrementally: complete one component/section + tests + Pint before moving to the next.
- Keep all hardcoded colors to DaisyUI semantic tokens to preserve theme compatibility.
- Prefer small, composable Livewire components over one giant `Welcome` component.
- After this plan is implemented, consider reusing the same visual language and components (cards, section headers, theme switcher) on the user dashboard and other user-facing pages for a cohesive experience.

