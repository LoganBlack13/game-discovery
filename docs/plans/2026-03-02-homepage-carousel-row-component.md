# Homepage Carousel Row Component Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the ad-hoc homepage carousels with a reusable, fully-visible card-row component that keeps all cards the same size, hides scrollbars, supports arrows and drag/gesture sliding, and fixes card border alignment and hover clipping issues across sections like “Coming Soon”, “Discover by Mood”, and “Trending Now”.

**Architecture:** Introduce a generic horizontal card-row Blade component (optionally wrapped in a small Livewire helper when data loading or pagination is needed) that uses a flex/inline-flex rail inside an overflow-x container with hidden scrollbars. Navigation arrows will scroll the rail by fixed card multiples using Alpine or Livewire actions; pointer and touch events will allow drag-to-scroll on desktop and swipe on mobile. Existing homepage sections will be refactored to consume this component with consistent card sizing, border radius, and spacing.

**Tech Stack:** Laravel 12, Blade, Livewire 4 (for data/interaction where needed), Tailwind CSS 4, DaisyUI 5, Alpine.js (for lightweight carousel interactions), Pest for tests.

---

## Task 1: Inventory existing homepage carousels and card usage

**Files:**
- Inspect: `resources/views/pages/⚡welcome.blade.php`
- Inspect: `resources/views/components/game/card.blade.php`
- Inspect: `resources/views/components/game/hero-tile.blade.php`
- Inspect: `resources/views/components/ui/signal-card.blade.php`
- Inspect: any existing partials/components used for “Coming soon”, “Discover mood”, “Trending now”, or similar rails.

**Step 1: Identify all carousel-like sections on the homepage**

- Open `⚡welcome.blade.php` and note each section that currently behaves like a horizontal rail or carousel:
  - “Coming Soon”
  - “Discover Mood”/“Discover by Mood”
  - “Trending Now”
  - Any other horizontally scrolling game rows or news rails.
- For each, write down:
  - Which Blade components they use for cards (e.g. `x-game.card`, `x-game.hero-tile`, `x-ui.signal-card`).
  - Whether the container uses `overflow-x-auto`, custom scrollbar classes, or JS.

**Step 2: Document existing layout and hover issues**

- In each section, note:
  - Where scrollbars are visible (desktop browsers, mobile) and which element they are attached to.
  - Where card hover borders are clipped by the container (i.e. card grows/has border-radius beyond the visible area).
  - Any mismatches between card border radius and the parent section’s border radius or background.
- Record these findings in comments at the bottom of this plan so they can be checked off in later tasks.

**Step 3: Note card sizing and content variability**

- For `game/card`, `hero-tile`, and `signal-card`:
  - Confirm what props determine height (media vs content).
  - Check if any components allow variable height based on description length, tag count, etc.
- Decide on a target **card width** and **card height** per variant (e.g. standard game card vs hero tile) that all carousel rows should snap to.

---

## Task 2: Design the reusable horizontal card-row API

**Files:**
- Create: `resources/views/components/ui/card-row.blade.php`
- Optional later: `app/Livewire/UI/CardRow.php` (only if data loading/pagination needs to move into Livewire).

**Step 1: Define the Blade component interface**

- Create the `ui.card-row` component with:
  - Props:
    - `title` (string) – section heading (e.g. “Coming Soon”).
    - `subtitle` (nullable string) – optional helper text.
    - `id` (string) – unique DOM id for the row (for JS targeting).
    - `cardWidth` (string, default e.g. `w-56`) – Tailwind width class applied to each card wrapper.
    - `cardHeight` (string, default e.g. `h-80`) – Tailwind height class for card wrapper or inner container.
    - `showArrows` (bool, default `true`).
    - `variant` (string, e.g. `default`, `hero`, `compact`) – used to tweak spacing if needed.
  - Slots:
    - Default slot for card content (callers pass `x-game.card`, etc.).
    - Optional `actions` slot for small filters/CTAs on the right side of the header.

**Step 2: Layout structure for consistent sizing**

- Inside the component:
  - Wrap the entire section in a `section` with consistent padding matching other homepage sections.
  - Use a header row that can reuse or internally mirror `x-ui.section-header`:
    - Left: title + subtitle.
    - Right: `actions` slot and arrow buttons (if `showArrows`).
  - For the rail:
    - Outer container:
      - `relative w-full overflow-hidden` (to hide card overflow and scrollbars).
    - Scroll container:
      - `flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth` with **hidden scrollbars** (plan to use custom scrollbar-hiding utilities).
    - Each card wrapper:
      - `shrink-0` with fixed `cardWidth` and `cardHeight`, and `snap-start`.
      - Internal alignment class (e.g. `flex flex-col`) so card content stretches properly.

**Step 3: Interaction and accessibility design**

- Arrow buttons:
  - Two buttons (`Previous`, `Next`) with:
    - Clear icons (chevrons), `aria-label` attributes, and focus outlines.
    - Disabled state when at the start/end if this can be determined.
- Scrolling behavior:
  - Each arrow click should scroll by **exactly one or multiple card widths** so the next card is fully visible (no partial clipping).
  - Keyboard accessibility:
    - Users should be able to tab to the scroll container and use arrow keys to scroll (browser default) or, optionally, add a small helper for left/right keys to call the same scroll logic.

---

## Task 3: Implement CSS utilities for hidden scrollbars and card sizing

**Files:**
- Modify: `resources/css/app.css`
- Potentially modify: Tailwind config CSS `@theme` block if custom tokens are needed.

**Step 1: Add cross-browser scrollbar hiding styles**

- In `app.css`, add utility classes (or component-level classes) to hide horizontal scrollbars without disabling scroll:
  - A utility like `.scrollbar-none` or `.scrollbar-hidden` that:
    - Sets `scrollbar-width: none;` for Firefox.
    - Uses `::-webkit-scrollbar { display: none; }` for WebKit-based browsers.
  - Ensure the rule is scoped to elements like `.carousel-row` / `.card-row-scroll` to avoid global scrollbar removal.

**Step 2: Define shared card size helpers**

- Add CSS (or Tailwind `@apply`) for consistent card wrappers, e.g.:
  - `.card-row-item` with `display:flex`, fixed min/max width and height, and alignment for inner `card`.
  - Ensure these helpers enforce consistent height even if inner content length varies.
- Keep sizing tokens compatible with Tailwind v4 (rem-based widths/heights or Tailwind width classes where possible).

**Step 3: Plan border radius alignment**

- Define or reuse shared radius tokens so:
  - The card outer container and the visible card match (`rounded-2xl` etc.).
  - Section backgrounds and cards don’t clash (e.g. no double-rounded frames unless explicitly desired).
- Note in this plan which radius and shadow levels should be considered the standard for homepage cards.

---

## Task 4: Build the `ui.card-row` Blade component

**Files:**
- Create: `resources/views/components/ui/card-row.blade.php`

**Step 1: Implement Blade structure and props**

- Define the component props as designed in Task 2.
- Implement markup:
  - Header row with title, subtitle, optional actions, and arrows.
  - `div` wrapping the scroll container with a specific class (e.g. `card-row-scroll`) using:
    - `overflow-x-auto`, `scroll-smooth`, `scrollbar-hidden` (from Task 3), and `snap-x snap-mandatory`.
  - Use Blade `{{ $slot }}` for card content, but wrap each card in an enforcing container class when needed (or document that callers must wrap with `x-slot` pattern).

**Step 2: Integrate border radius and hover-safe padding**

- Ensure the scroll container and card wrappers:
  - Provide enough internal padding so that when a card elevates or adds a ring/shadow on hover, it is **not clipped** by the container.
  - Use `overflow-visible` on the card wrapper if needed, while keeping `overflow-hidden` only on the outermost row container to hide scrollbars.
- Confirm:
  - Border radius on the card wrapper matches the card content’s radius (`rounded-xl` vs `rounded-2xl`) so borders align.

**Step 3: Basic responsiveness**

- Add responsive tweaks:
  - On mobile, allow more generous horizontal padding so users can start swiping without hitting the absolute edge of the viewport.
  - Ensure no horizontal page-level scrollbar appears; only the row itself should scroll.

---

## Task 5: Wire up arrow-based scrolling with Alpine.js

**Files:**
- Modify: `resources/views/components/ui/card-row.blade.php`
- Confirm/Inspect: any Alpine initialization in `layouts/app.blade.php`.

**Step 1: Add Alpine state and refs**

- In the `card-row` component, wrap the scroll container in an Alpine scope:
  - `x-data="{ scrollByCards(step = 1) { /* implementation */ } }"`.
  - Use `x-ref="scroll"` on the element that should be scrolled.

**Step 2: Implement `scrollByCards` behavior**

- In the Alpine method:
  - Find a single card element within the scroll container to measure its `offsetWidth` (including gap if needed).
  - Compute scroll amount: `amount = step * cardWidth`.
  - On left arrow click: `scrollLeft = scrollLeft - amount`.
  - On right arrow click: `scrollLeft = scrollLeft + amount`.
  - Use `scrollTo({ left: newLeft, behavior: 'smooth' })` for smooth motion.

**Step 3: Bind arrow buttons**

- Add `x-on:click` handlers on the arrow buttons:
  - Left: `@click="scrollByCards(-1)"`
  - Right: `@click="scrollByCards(1)"`
- Ensure buttons are keyboard-focusable and have visible focus styles consistent with DaisyUI.

---

## Task 6: Add drag/swipe-to-scroll interactions

**Files:**
- Modify: `resources/views/components/ui/card-row.blade.php`

**Step 1: Desktop drag with mouse**

- Extend Alpine state with variables like `isDown`, `startX`, `scrollLeft`.
- Bind mouse events on the scroll container:
  - `@mousedown` – set `isDown = true`, record `startX` and current `scrollLeft`.
  - `@mousemove` – if `isDown`, calculate delta and update `scrollLeft` accordingly.
  - `@mouseleave` / `@mouseup` – set `isDown = false`.
- Ensure the drag behavior:
  - Does not interfere with clicking on cards (add a small threshold before treating as drag).

**Step 2: Touch swipe on mobile**

- Add `@touchstart`, `@touchmove`, `@touchend` handlers:
  - Mirror the mouse logic using `touches[0].pageX` and manage `scrollLeft`.
- Make sure:
  - Vertical scrolling of the page remains possible (only horizontal gestures primarily affect the carousel).

**Step 3: Verify scrollbars remain hidden**

- Confirm that during drag/scroll:
  - The scroll container still uses the scrollbar-hiding utilities from Task 3.
  - No OS-level scrollbars appear on common browsers.

---

## Task 7: Normalize card sizing and border radius across components

**Files:**
- Modify: `resources/views/components/game/card.blade.php`
- Modify: `resources/views/components/game/hero-tile.blade.php`
- Modify: `resources/views/components/ui/signal-card.blade.php`

**Step 1: Standardize outer wrappers for rail usage**

- For each card-like component, ensure:
  - A consistent outer `div` wrapper used when placed within a rail (class like `card-rail-item`).
  - This wrapper receives the fixed width/height classes and radius from Task 3.
  - Inner card content uses `w-full h-full` to fill the wrapper.

**Step 2: Align border radius between wrapper and content**

- Update border radius classes so:
  - There is a single source of truth (e.g. `rounded-2xl`) applied either to the wrapper or the inner card, but not conflicting values on both.
  - Hover states (rings, shadows) extend outward without being clipped; adjust `overflow` on inner containers accordingly.

**Step 3: Handle content differences while preserving size**

- For longer text or additional badges:
  - Use `line-clamp` utilities where necessary to keep height consistent.
  - Use flexbox (`flex-col justify-between`) to distribute space predictably.
- Confirm:
  - All cards in a row visually share the same dimensions regardless of individual content length.

---

## Task 8: Refactor homepage sections to use `ui.card-row`

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Modify: any partials currently rendering “Coming Soon”, “Discover Mood”, “Trending Now”, etc.

**Step 1: Replace ad-hoc containers with `x-ui.card-row`**

- For each relevant section:
  - Wrap the existing cards with:
    - `<x-ui.card-row id="coming-soon" title="Coming Soon"> ... </x-ui.card-row>`
    - `<x-ui.card-row id="discover-mood" title="Discover by Mood"> ... </x-ui.card-row>`
    - `<x-ui.card-row id="trending-now" title="Trending Now"> ... </x-ui.card-row>`
  - Remove direct `overflow-x-auto` or custom arrow implementations from the page; let the component handle them.

**Step 2: Ensure section-specific actions still work**

- If sections have filters (e.g. mood chips, time-frame toggles):
  - Render those controls in the `actions` slot of `card-row` so they appear aligned with the section header.
  - Confirm Livewire/Alpine bindings still work as expected after being moved into the slot.

**Step 3: Verify no card clipping and consistent sizing**

- Manually check each section:
  - Arrows scroll by full-card increments.
  - No card’s hover border or shadow is clipped by the container.
  - Cards share the same width/height regardless of content.

---

## Task 9: Testing and visual QA

**Files:**
- Create/Modify: `tests/Feature/HomepageCarouselRowTest.php` (or extend existing homepage tests)
- All modified Blade components and CSS.

**Step 1: Feature tests for presence and structure**

- Write Pest tests that:
  - Assert the homepage renders sections with `data-testid` or ids for “coming-soon-row”, “discover-mood-row”, and “trending-now-row`.
  - Ensure the HTML contains the arrow buttons for each row.
  - Verify that cards have consistent wrapper classes (width/height/radius).

**Step 2: Livewire/Alpine interaction tests (where feasible)**

- If any row is backed by Livewire:
  - Add tests confirming that changing filters still updates the list of cards.
  - (Optional) For deeper coverage, consider browser tests later to verify real scroll behavior.

**Step 3: Manual visual QA**

- On desktop:
  - Confirm no visible horizontal scrollbars on any card row.
  - Test mouse drag and arrow clicks for each section.
  - Confirm hover effects do not clip and borders align with content.
- On mobile (e.g. Chrome dev tools emulator):
  - Confirm horizontal swipe gestures work and scrollbars are not visible.
  - Ensure the page itself does not get an unintended horizontal scrollbar.

**Step 4: Code style and linting**

- Run: `vendor/bin/pint --dirty` to fix formatting.
- Run targeted tests: `php artisan test --compact tests/Feature/HomepageCarouselRowTest.php` (or equivalent).

---

## Notes & Acceptance Criteria Checklist

- **No visible scrollbars:** All horizontal rails hide scrollbars while still allowing scroll via arrows, mouse drag, and touch swipe.
- **Arrow navigation:** Each row has left/right arrow controls that move by whole-card increments; cards are fully visible after navigation.
- **Drag/swipe support:** Users can drag with mouse on desktop and swipe on mobile to move the carousel.
- **No hover clipping:** Card hover borders, rings, and shadows are never cut off by the container.
- **Aligned border radius:** Card borders and parent section borders share consistent radii, with no mismatched rounding.
- **Uniform card sizing:** All cards in a row share the same width and height regardless of content differences.
- **Reusable API:** New horizontal card-based sections can be created using `x-ui.card-row` without re-implementing carousel logic.

