# Remove Flux from User Frontend, Add DaisyUI 5, Redesign Homepage

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing. The attached **image is the source of truth** for the homepage layout, typography, colors, and component placement. Match it strictly.

**Goal:** (1) Remove the Flux library from the **user-facing** part of the site (app layout and all views rendered within it). (2) Keep Flux on the **admin layout** only. (3) Install DaisyUI version 5 for the user frontend. (4) Update Rules and Skills to include DaisyUI. (5) Redesign the homepage to use DaisyUI and match the provided target image as closely as possible, creating custom Blade components where needed.

**Architecture:** User area uses `layouts.app` and loads CSS that includes Tailwind v4 + DaisyUI 5 only (no Flux). Admin area uses `layouts.admin` and loads the same base CSS plus a separate `admin.css` that imports Flux, so admin keeps Flux components. Theme switching on the app layout is reimplemented with Alpine.js and HTML (e.g. `data-theme` or class on `<html>`) so it works without Flux; DaisyUI themes can be used for light/dark. Homepage (welcome) and all user Livewire components (dashboard feed, dashboard game list, game search modal) are migrated from Flux to DaisyUI or custom components. The **image provided is the definitive visual spec** for the homepage.

**Tech stack (user frontend after change):** Laravel 12, Livewire 4, **DaisyUI 5**, Tailwind v4, Alpine.js. Admin frontend: unchanged (Flux + Tailwind v4).

**Key files:**
- [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php) – remove `@fluxAppearance`, `@fluxScripts`, all `<flux:*>`; add DaisyUI-based nav and theme toggle
- [resources/views/layouts/admin.blade.php](resources/views/layouts/admin.blade.php) – keep Flux; add Vite entry for `admin.css` so Flux CSS loads only on admin
- [resources/css/app.css](resources/css/app.css) – remove Flux import; add `@plugin "daisyui"`
- New: `resources/css/admin.css` – only `@import` Flux CSS (so admin pages get Flux)
- [vite.config.js](vite.config.js) – add `resources/css/admin.css` to input; admin layout references both app.css and admin.css
- [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php) – full redesign to match image (hero, sections, cards, news)
- [resources/views/components/⚡welcome-news.blade.php](resources/views/components/⚡welcome-news.blade.php) – restyle to match “LATEST NEWS” in image
- [resources/views/components/⚡dashboard-feed.blade.php](resources/views/components/⚡dashboard-feed.blade.php) – replace `flux:button` with `btn` / custom
- [resources/views/components/⚡dashboard-game-list.blade.php](resources/views/components/⚡dashboard-game-list.blade.php) – replace `flux:card` with DaisyUI card or custom
- [resources/views/components/⚡game-search-modal.blade.php](resources/views/components/⚡game-search-modal.blade.php) – replace `flux:input`, `flux:button`; remove `data-flux-input` reference in keydown handler
- Rules: [.cursor/rules/laravel-boost.mdc](.cursor/rules/laravel-boost.mdc), [CLAUDE.md](CLAUDE.md), [AGENTS.md](AGENTS.md)
- Skills: [.cursor/skills/frontend-design/SKILL.md](.cursor/skills/frontend-design/SKILL.md) (and any other skills that reference frontend stack)

---

## Out of scope / assumptions

- Admin area (layouts.admin and all admin Livewire views) continues to use Flux; no migration to DaisyUI there.
- Dashboard, auth, profile, and game show pages keep current structure; they only stop using Flux components (replaced by DaisyUI or plain HTML/Tailwind). Full visual redesign is **homepage only**; other user pages get consistent styling (DaisyUI where it fits) without a full mockup pass.
- “Strictly respect image” applies to the **homepage**: header, hero, game sections, latest news. Other user pages should feel consistent (dark theme, teal accent) but are not required to match a mockup pixel-for-pixel.

---

## Target homepage design (from image — source of truth)

- **Header:** Full-width dark background. **Left:** Logo (stylized “S” icon, white). **Center:** Primary nav: “Games” (with dropdown arrow), “Trending”, “News”, “About” (white text; “Games” has subtle grey bg + glow on hover/focus). **Right:** Search icon (magnifying glass), theme toggle: capsule with sun (left) and moon (right), moon side highlighted for dark mode.
- **Hero:** Same dark background with **scattered teal/cyan glowing particle dots**. Title: **“DISCOVER YOUR NEXT GAME.”** — large, bold, white, **one word per line** (stacked, centered). Subtitle: “Curated picks, hidden gems, and trending titles. One place to find what you'll play next.” — smaller, white, centered. Two CTAs: **Primary:** “Explore games” — solid teal/cyan button, rounded, white text, subtle glow; **Secondary:** “See what's trending” — text-only, white, no background.
- **Game sections:** Three sections: **COMING SOON**, **TRENDING NOW**, **RECENTLY RELEASED**. Each: large bold white section title (left-aligned); **horizontal row** of game cards (scrollable). **Card:** Dark grey rounded rectangle; top = game cover image; below: **game title** (bold white), **release date or status** (e.g. “Mar 19, 2026”, “Trending NOW”) in light grey, **platforms** in smaller light grey. **Hover:** Teal/cyan glowing border and card slightly elevated (translate up or shadow).
- **Latest news:** Section title “LATEST NEWS” (large bold white, left). **Vertical list:** Each item = row: **small square thumbnail** (left), **news title** (bold white, can wrap), **source and date** (e.g. “Strand - Feb 28, 2026”) in small light grey below title. Thin horizontal separator between items.

**Colors:** Dark grey/black background; white primary text; light grey secondary; **teal/cyan** accent (buttons, hover glow, section labels). Typography: clean sans-serif; bold for headings.

---

## Task 1: CSS split — remove Flux from app, add DaisyUI, keep Flux for admin

**Files:** [resources/css/app.css](resources/css/app.css), new `resources/css/admin.css`, [vite.config.js](vite.config.js), [resources/views/layouts/admin.blade.php](resources/views/layouts/admin.blade.php)

- **app.css:** Remove `@import '../../vendor/livewire/flux/dist/flux.css';`. Add DaisyUI 5: `@plugin "daisyui";` (after `@import "tailwindcss";`). Install daisyUI: `bun add -D daisyui@latest`. Keep existing `@theme`, `@source`, keyframes, and custom classes.
- **admin.css (new):** Create `resources/css/admin.css` containing only the Flux import: `@import '../../vendor/livewire/flux/dist/flux.css';`
- **vite.config.js:** Add `resources/css/admin.css` to the `input` array so it is built (e.g. `input: ['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js']`).
- **admin layout:** In `resources/views/layouts/admin.blade.php`, ensure both CSS files are loaded: `@vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])`. This way admin gets Tailwind + DaisyUI + Flux; user pages get only Tailwind + DaisyUI (app layout already loads only `app.css` via existing `@vite`).

---

## Task 2: App layout — remove Flux, add nav and theme toggle to match image

**Files:** [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php)

- Remove `@fluxAppearance` and `@fluxScripts` from `<head>` and before `</body>`.
- **Header structure (match image):** Dark background (`bg-base-300` or equivalent dark); full width. **Left:** Logo — link to home with “S” or app logo (use existing app name or logo asset). **Center:** Nav links: Games (with dropdown indicator; can be link or dropdown to `/games` or `#coming-soon`), Trending (`#trending` or route), News (e.g. `#latest-news` or route), About (route or placeholder). **Right:** Search button (icon only or “Search”), theme toggle.
- **Search button:** Replace `<flux:button ... @click="$dispatch('open-game-search')">` with a plain `<button>` or `<button class="btn btn-ghost btn-sm btn-square">` with magnifying-glass icon (SVG or Heroicon), same `@click` and `aria-label`.
- **Theme toggle:** Replace Flux radio group with a custom toggle. Use Alpine.js: state for `theme` (e.g. `'light' | 'dark' | 'system'`). Apply theme via `data-theme` on `<html>` (DaisyUI) or class `dark` for Tailwind. Store preference in `localStorage`; on load read and set. Toggle UI: capsule with sun (left) and moon (right); highlight current (e.g. dark = moon). Click sun → light, moon → dark (and optionally system). No Flux dependency.
- Keep the existing game-search modal container and `livewire:game-search-modal`; only the header controls change.

---

## Task 3: Game search modal — replace Flux with DaisyUI / native elements

**Files:** [resources/views/components/⚡game-search-modal.blade.php](resources/views/components/⚡game-search-modal.blade.php)

- Replace `<flux:input type="search" ...>` with a native `<input type="search">` or DaisyUI input (e.g. `class="input input-bordered w-full"`), same `wire:model.live.debounce.300ms="query"`, placeholder, `aria-label`.
- In the keydown handler, remove the check for `data-flux-input`; use a generic check (e.g. `target.closest('input, textarea')` or `target.matches('input, textarea')`) so keyboard nav still skips when focus is in the search input.
- Replace both `<flux:button>` (Track / Remove from tracking) with `<button class="btn btn-ghost btn-sm">` and `<button class="btn btn-primary btn-sm">` (or equivalent DaisyUI button classes), same `wire:click`, `wire:loading.attr`, `aria-label`, and inner text/spans.

---

## Task 4: Homepage (welcome) — full redesign to match image

**Files:** [resources/views/pages/⚡welcome.blade.php](resources/views/pages/⚡welcome.blade.php), new optional Blade components (e.g. `resources/views/components/game-card.blade.php` for homepage card style).

- **Hero:** Background: dark (same as header) with **teal/cyan particle-like dots** (CSS only: e.g. radial gradients or small pseudo-elements / divs with blur and low opacity). Title: “DISCOVER YOUR NEXT GAME.” — one word per line (e.g. separate spans or `<br>`), large bold white, centered. Subtitle: single line, smaller white, centered. Buttons: primary = “Explore games” (teal/cyan solid, DaisyUI `btn btn-primary` or custom with teal bg), link to `#coming-soon`; secondary = “See what's trending” (ghost/text, `btn btn-ghost` or link), link to `#trending`. Match image spacing and hierarchy.
- **Game sections:** Keep existing Livewire data (getUpcomingGames, getPopularGames, getRecentlyReleasedGames). **Remove all `<flux:card>`.** Section titles: “COMING SOON”, “TRENDING NOW”, “RECENTLY RELEASED” — large bold white, left-aligned. Each section: single horizontal row of cards; use `flex flex-nowrap gap-4 overflow-x-auto` (or similar). **Card:** Create a reusable Blade component or inline: dark grey rounded card; top = game cover (aspect ratio as in image); then title (bold white), then date/status (light grey), then platforms (smaller light grey). **Hover:** Teal/cyan ring or shadow and slight translateY (e.g. `hover:-translate-y-0.5 hover:ring-2 hover:ring-cyan-500`). Use DaisyUI `card` + `card-body` and override with custom classes if needed, or build with Tailwind only to match image exactly.
- **Latest news:** Keep `<livewire:welcome-news />`; styling of that section is updated in Task 5. Ensure section id/anchor for “LATEST NEWS” (e.g. `id="latest-news"`) for nav link from header.

---

## Task 5: Welcome news component — match “LATEST NEWS” from image

**Files:** [resources/views/components/⚡welcome-news.blade.php](resources/views/components/⚡welcome-news.blade.php)

- Section title: “LATEST NEWS” — large bold white, left-aligned (same style as game section titles).
- List: vertical; each item = horizontal row. **Left:** Small **square thumbnail** (game cover or placeholder); fix size (e.g. `h-14 w-14` or similar). **Right:** News title (bold white, multi-line allowed), below it source and date in small light grey (e.g. “Source - Feb 28, 2026”). Use a thin divider between rows (e.g. `border-b border-base-content/10` or similar). Keep existing infinite scroll (sentinel + `loadMore`). Remove or adjust any card style that doesn’t match (e.g. rounded-xl/card look if image shows flatter list rows).

---

## Task 6: Dashboard feed and dashboard game list — replace Flux

**Files:** [resources/views/components/⚡dashboard-feed.blade.php](resources/views/components/⚡dashboard-feed.blade.php), [resources/views/components/⚡dashboard-game-list.blade.php](resources/views/components/⚡dashboard-game-list.blade.php)

- **dashboard-feed:** Replace “Load more” `<flux:button>` with `<button class="btn btn-ghost btn-sm">` (or equivalent DaisyUI), same `wire:click`, `aria-label`.
- **dashboard-game-list:** Replace each `<flux:card>` wrapping a game with a DaisyUI card (e.g. `div` with `class="card ..."` and `card-body`) or the same custom card component used on the homepage if it fits. Preserve link, image, title, release date, platforms, and any other content. Style to be consistent with app (dark theme, teal accent where appropriate).

---

## Task 7: DaisyUI theme and customizations

**Files:** [resources/css/app.css](resources/css/app.css), optionally [tailwind.config.js](tailwind.config.js) or DaisyUI theme in CSS

- Configure DaisyUI so the default (or chosen) theme matches the image: dark base, teal/cyan as primary (or accent). Use DaisyUI themes (e.g. `data-theme="dark"` with a custom theme) or `@theme` in app.css to set `--p` (primary) to the teal/cyan value. Ensure `html` or body has the correct `data-theme` when user selects dark/light so both DaisyUI and existing dark: utilities behave.
- If the app layout uses `class="dark"` on `<html>` for Tailwind dark mode, keep that in sync with the theme toggle (Alpine sets both `data-theme` for DaisyUI and `class="dark"` if needed). Prefer a single source of truth (e.g. Alpine state + `data-theme` and DaisyUI’s dark theme).

---

## Task 8: Rules and Skills updates

**Files:** [.cursor/rules/laravel-boost.mdc](.cursor/rules/laravel-boost.mdc), [CLAUDE.md](CLAUDE.md), [AGENTS.md](AGENTS.md), [.cursor/skills/frontend-design/SKILL.md](.cursor/skills/frontend-design/SKILL.md)

- **Rules (laravel-boost.mdc, CLAUDE.md, AGENTS.md):** In “Foundational Context” or “Tech stack”, add **daisyui (DAISYUI) - v5** for the user frontend. In Tailwind-related sections, state that the **user-facing** UI uses **Tailwind v4 + DaisyUI 5**; admin continues to use Flux where already present. Optionally add a short line: “User frontend: DaisyUI 5 components and semantic class names (btn, card, input, etc.); prefer DaisyUI classes over raw Tailwind for common UI elements on app layout and user pages.”
- **frontend-design skill:** Add a note that for this project, user-facing pages use **DaisyUI 5** on top of Tailwind v4; when creating or styling components for the app (non-admin), prefer DaisyUI semantic components and theme variables. Design choices should still follow the skill’s aesthetic guidelines (typography, color, motion, etc.) while using DaisyUI where it fits.

---

## Task 9: Tests and polish

**Files:** [tests/Feature/WelcomePageTest.php](tests/Feature/WelcomePageTest.php), any other feature tests that assert on Flux or homepage structure.

- Update or add tests: welcome page loads, hero and section titles are present, game sections show (Coming soon, Trending, Recently released), latest news section is present. If tests target Flux component names or classes, update selectors to new structure (e.g. headings, links, buttons). Ensure no test expects `flux:button` or `flux:card` on the welcome page or app layout.
- Run `vendor/bin/pint --dirty` on touched PHP.
- Run `bun run build` and smoke-check homepage (and one admin page) in browser; confirm theme toggle, search modal, and nav work. Confirm admin still has Flux styling and components.

---

## Summary

| Step | Action |
|------|--------|
| 1 | CSS split: app.css = Tailwind + DaisyUI (no Flux); new admin.css = Flux only; Vite + admin layout load both on admin |
| 2 | App layout: remove Flux directives and Flux components; add nav + search button + custom theme toggle (Alpine + data-theme) to match image |
| 3 | Game search modal: replace flux:input and flux:button with native/DaisyUI; fix keydown selector |
| 4 | Homepage: hero (particles, stacked title, two CTAs), three game sections with horizontal cards (no Flux), hover teal glow; match image |
| 5 | Welcome-news: “LATEST NEWS” title and list layout (thumbnail left, title + source/date, dividers) |
| 6 | Dashboard feed and game list: replace Flux button and cards with DaisyUI/custom |
| 7 | DaisyUI theme (dark + teal primary) and theme toggle sync with data-theme / class |
| 8 | Rules and Skills: add DaisyUI v5, user vs admin frontend distinction |
| 9 | Tests and Pint; build and manual check |

---

## Notes

- **DaisyUI 5 + Tailwind v4:** Install with `bun add -D daisyui@latest`. In CSS: `@import "tailwindcss";` then `@plugin "daisyui";`. No separate config required for basic use; themes can be set via `data-theme` on `<html>`.
- **Theme persistence:** Store `theme` in `localStorage` (e.g. `game-discovery-theme`); on load read and set `document.documentElement.dataset.theme` (and optional `class="dark"` for Tailwind). Default to `system` (match `prefers-color-scheme`) if not set.
- **Custom components:** Prefer creating Blade components in `resources/views/components/` for repeated patterns (e.g. homepage game card, theme toggle) so the welcome view stays clean and consistent with the image.
- **Image as source of truth:** Any ambiguity in layout (spacing, order, label text) should be resolved by the provided homepage image description: dark theme, teal accents, stacked hero title, horizontal game rows, news list with thumbnail + title + source/date.
