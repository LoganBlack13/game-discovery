# Homepage Hero Rework Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rework the homepage Hero so it surprises, reads clearly, and feels visual. Fix the confusing “Your signals” right column and make the left-side game block obviously explain *why* those games are shown.

**Architecture:** Keep the existing two-column Hero layout and data (popular games for hero; no new backend). Changes are copy, structure, and visual treatment: (1) replace “Your signals” with a clear, scannable right column (e.g. “Jump in” or “Ways to discover” with honest labels and optional visual flair); (2) left side: one clear headline + explicit reason for the featured games (e.g. “Trending with the community”) and optional single bold hero visual; (3) add at least one distinctive visual element (gradient, shape, or typography) so the Hero feels memorable.

**Tech Stack:** Laravel 12, Livewire (existing anonymous welcome component), Blade, Tailwind v4, DaisyUI 5. No new dependencies.

---

## Problem Summary

| Issue | Current state | Desired |
|-------|----------------|--------|
| **Surprise** | Hero feels generic. | One clear visual hook (e.g. strong gradient, one big game visual, or typography moment). |
| **“Your signals”** | Right column title implies user-specific data; content is feature teasers (Session planner, Backlog heat, Hidden gems). | Rename and reframe so it’s obviously “ways to discover” or “quick actions,” not “your” data. |
| **Why these games?** | Left shows popular games with hardcoded “Popular with players” / “Great fit tonight” and “Tonight’s pick” with no visible logic. | One honest line (e.g. “Trending this week” or “Most tracked right now”) and optional per-tile reason so users know why they’re there. |
| **Visual** | Text-heavy; hero tile is nice but not a strong visual anchor. | More visual: stronger imagery hierarchy, optional full-bleed or bold accent, clear focal point. |

---

## Design Direction (Concept)

- **Left column:** One short headline that answers “What is this?” (e.g. “Trending with the community” or “What everyone’s tracking now”). One primary hero game (large) + up to 3 secondary, with a visible reason under or beside the block (e.g. “Most tracked this week”). Consider a single bold visual: e.g. one large cover with a subtle gradient or glow so the eye lands there first.
- **Right column:** Replace “Your signals” with a clear section title (e.g. “Jump in” or “Ways to discover”). Keep 3 cards but relabel so they’re obviously features/actions: e.g. “Session planner” → “Pick a game for tonight,” “Backlog heat” → “Hot this week,” “Hidden gems” → “Underrated picks.” Optional: add a small visual (icon, illustration, or gradient card) so the column feels more visual than text-only.
- **Surprise element:** Choose one: (a) strong gradient or mesh background behind the Hero, (b) one oversized game cover with clear “Trending now” badge, or (c) a short, bold typography line (e.g. “What to play tonight”) with a clear subline. Implement one in this iteration.

---

## Task 1: Clarify and rename the right column (“Your signals”)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php` (Hero section, right column)
- Optionally: `resources/views/components/ui/section-header.blade.php` (if only copy changes)

**Step 1: Decide new section identity**

- Replace title **“Your signals”** and subtitle **“Backlog, progress & hype”** with a title that describes *actions/features*, not user data. Examples: **“Jump in”** / “Quick ways to discover,” or **“Ways to discover”** / “Session planner, trending list, hidden gems.”
- Document the chosen title and subtitle in this plan or in a short comment above the section in the Blade file.

**Step 2: Update copy in the welcome page**

- In `resources/views/pages/⚡welcome.blade.php`, update the `<x-ui.section-header>` for the right column to use the new title and subtitle.
- Update each of the three signal cards so the **label** and **value** (and slot copy if needed) make it obvious these are features/entry points, not “your” personal signals. For example:
  - “Now playing” / “Session planner” → e.g. “Tonight” / “Session planner” or “Quick pick” / “Fit your mood & time.”
  - “Backlog heat” / “Hot this week” → e.g. “Trending” / “Hot this week” or keep label but ensure value reads as a feature.
  - “Hidden gems” / “Shortlist” → e.g. “Curated” / “Hidden gems” or “Underrated” / “Shortlist.”
- Ensure the slot text (description) under each card still makes sense with the new labels.

**Step 3: Verify and commit**

- Load the welcome page in the browser; confirm the right column no longer reads as “your” data.
- Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
- Commit: `git add resources/views/pages/⚡welcome.blade.php` (and any modified section-header or signal-card usage), `git commit -m "content: rename Hero right column from Your signals to Jump in / Ways to discover"`

---

## Task 2: Left column — explain why these games are here

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php` (Hero left column: header and game block)
- Modify: `resources/views/components/game/hero-tile.blade.php` (reasons / context)

**Step 1: Add one clear “why” line for the hero block**

- In the Hero left column, add a single line that explicitly states why the games are shown. Examples: “Trending with the community,” “Most tracked this week,” or “What everyone’s tracking now.” Place it above or directly under the main headline (e.g. under “Discover what to play tonight” or replace that line so the “why” is in the headline area).
- Ensure the small uppercase label (e.g. “Signal grid arcade”) and the new “why” line work together so a first-time user immediately understands the block.

**Step 2: Make hero tile reasons data-driven or honest**

- Today `getHeroGames()` returns `getPopularGames()` (by `tracked_by_users_count`). Either:
  - **Option A:** Pass one honest reason from the backend (e.g. “Trending this week” or “Most tracked”) into `<x-game.hero-tile>` and show it instead of hardcoded “Popular with players” / “Great fit tonight”; or
  - **Option B:** Remove generic “reasons” from the hero tile and rely on the new section “why” line so the big tile only shows game title, image, and CTA.
- Implement one option in the Blade and, if needed, in the anonymous Livewire class (e.g. a method or property that returns the reason string for the hero block).

**Step 3: Secondary cards context**

- For the 3 smaller game cards next to the hero tile, ensure they don’t imply a different logic (e.g. avoid “Tonight’s pick” on the big tile if the block is “Trending”). If they share the same source (popular games), add a small shared label (e.g. “Also trending”) or leave them without a misleading “pick” badge. Update `resources/views/pages/⚡welcome.blade.php` and, if used, `resources/views/components/game/card.blade.php` (or the compact variant) so status/label is consistent.

**Step 4: Test and commit**

- Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
- Commit: `git add resources/views/pages/⚡welcome.blade.php resources/views/components/game/hero-tile.blade.php` (and any other touched files), `git commit -m "content: hero left column explains why games are shown (trending / most tracked)"`

---

## Task 3: Add one visual “surprise” to the Hero

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php` (Hero section wrapper and/or left column)
- Optionally: `resources/views/components/game/hero-tile.blade.php` or `resources/css/app.css` (if adding a utility or component-level class)

**Step 1: Choose and implement one visual element**

Pick one (or a small combination that stays minimal):

- **Background:** Add a gradient or soft mesh background to the Hero section (e.g. `bg-gradient-to-br from-primary/10 via-transparent to-secondary/5` or similar in Tailwind v4) so the Hero band feels distinct from the rest of the page.
- **Hero tile:** Make the primary game tile more dominant: e.g. slightly larger, stronger shadow/ring, or a clear “Trending now” badge on the tile so it’s the obvious focal point.
- **Typography:** One bold headline (e.g. larger or with a subtle glow/weight) and a single supporting line so the Hero has a clear typographic hierarchy.

Implement using only Tailwind v4 and existing DaisyUI tokens; no new CSS file unless a single custom utility is needed (then add to `app.css`).

**Step 2: Preserve accessibility and layout**

- Ensure contrast and focus states still meet existing standards; don’t reduce readability.
- Confirm the Hero still behaves on small viewports (stacking, spacing).

**Step 3: Test and commit**

- Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
- Commit: `git add resources/views/pages/⚡welcome.blade.php` (and any CSS/component changes), `git commit -m "ui: add visual emphasis to Hero (gradient / badge / typography)"`

---

## Task 4: Optional — right column visual lift

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php` (right column)
- Optionally: `resources/views/components/ui/signal-card.blade.php`

**Step 1: Add light visual interest to the right column**

- If time allows: add a subtle background or border to the right column container, or a small icon/illustration so “Jump in” / “Ways to discover” feels more visual. Keep it minimal so the left column remains the main focus.
- Do not introduce new assets or dependencies; use Tailwind/DaisyUI only.

**Step 2: Commit (optional)**

- `git add ... && git commit -m "ui: light visual lift for Hero right column"`

---

## Summary

| Task | Description |
|------|-------------|
| 1 | Rename “Your signals” and rewrite right-column copy so it’s clearly “Ways to discover” / “Jump in,” not user data. |
| 2 | Left column: one clear “why” line for the hero games; hero tile reasons honest or removed; secondary cards consistent. |
| 3 | One visual surprise: gradient/mesh, hero badge, or typography emphasis. |
| 4 | (Optional) Light visual lift for the right column. |

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-03-03-homepage-hero-rework.md`. Two execution options:

1. **Subagent-Driven (this session)** – Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run the plan task-by-task with checkpoints.

Which approach?
