# DaisyUI 5 Theming System Integration Plan

> **Goal:** Fix and improve the app’s DaisyUI 5 theming so it aligns with the [official themes docs](https://daisyui.com/docs/themes/) and works seamlessly with the [daisyUI theme generator](https://daisyui.com/theme-generator/). Custom themes should use the same format as the generator output, and adding or editing themes from the generator should be a straightforward copy-paste workflow.

**References:**
- [daisyUI themes documentation](https://daisyui.com/docs/themes/)
- [daisyUI theme generator](https://daisyui.com/theme-generator/)

---

## Current state (gaps)

1. **No DaisyUI plugin theme config**  
   `resources/css/app.css` uses `@plugin "daisyui";` with no `{ themes: ... }` block. DaisyUI therefore ships its default `light` and `dark` themes, while the app uses custom theme names (`arcade-night`, `daylight-pastel`, etc.) defined only via raw `[data-theme="..."]` selectors. Default and prefers-dark are not declared in the plugin.

2. **Custom themes are incomplete vs theme generator**  
   Custom themes are defined with **only color variables** in `[data-theme="..."]` blocks. Missing vs [theme generator](https://daisyui.com/theme-generator/) and [custom theme docs](https://daisyui.com/docs/themes/#how-to-add-a-new-custom-theme):
   - `color-scheme` (light/dark for browser UI)
   - `--radius-selector`, `--radius-field`, `--radius-box`
   - `--size-selector`, `--size-field` (and per-size if used)
   - `--border`
   - `--depth`, `--noise`
   So components don’t get consistent radius/size/border/effects, and generator output can’t be dropped in without changes.

3. **Theme list duplicated in three places**  
   - Inline script in `<head>`: `THEMES` array and `resolveThemeSlug()` (light/dark → slug mapping).
   - Alpine `themeToggle()` in layout: `themes` array (slug, label, swatch).
   - No single source of truth; adding a theme from the generator requires editing both plus CSS.

4. **No use of `@plugin "daisyui/theme"`**  
   The docs (and theme generator) use `@plugin "daisyui/theme" { name: "..."; default: true; prefersdark: false; color-scheme: light; ... }`. The app doesn’t use this at all, so generator output doesn’t match our structure.

5. **Tailwind `dark:` variant not tied to DaisyUI dark themes**  
   The app has `@custom-variant dark (&:where(.dark, .dark *));` but doesn’t map `dark:` to DaisyUI theme names (e.g. `arcade-night`). So `dark:` doesn’t follow `data-theme` and theme-specific overrides are done with long `[data-theme="light"]` / `[data-theme="daylight-pastel"]` selectors instead of a single `dark:`-aware convention.

6. **Theme-specific overrides are brittle**  
   `.homepage-full-bg-layer` and `.header-bar` have rules for `[data-theme="light"]` and `[data-theme="daylight-pastel"]`. Any new “light” theme from the generator would need to be added manually to those selectors.

---

## Target state

- **Single theme declaration format:** All custom themes defined via `@plugin "daisyui/theme" { ... }` with the full variable set (colors + radius, size, border, depth, noise, color-scheme), matching theme generator output.
- **Explicit plugin config:** `@plugin "daisyui" { themes: ... }` lists only the themes we use and sets `--default` and `--prefersdark` so DaisyUI and the app agree on defaults.
- **Theme generator workflow:** User can copy “Add theme to your CSS file” from the theme generator, paste a new `@plugin "daisyui/theme" { ... }` block (or merge into an existing theme), add the theme name to the plugin’s `themes` list and to the app’s theme picker, with no need to rewrite variables.
- **Single source of truth for theme list:** One place (config or a PHP/JSON list) drives both the layout’s theme picker (labels, slugs, swatches) and the head script’s resolution (so no duplicate THEMES array).
- **Semantic light/dark for overrides:** A clear rule for “light-like” vs “dark-like” themes (e.g. `color-scheme: light` or a small list of “light” theme slugs) so theme-specific overrides (hero, header) don’t require editing CSS for every new theme name.

---

## Task 1: Configure DaisyUI plugin and declare enabled themes

**File:** `resources/css/app.css`

- Replace bare `@plugin "daisyui";` with a config that explicitly lists themes and defaults, for example:

  ```css
  @plugin "daisyui" {
    themes: arcade-night --default --prefersdark, daylight-pastel, retro-crt, noir-minimal, cosmic-fade;
  }
  ```

- Use the actual theme slugs the app uses. Set:
  - One theme with `--default` (e.g. `arcade-night`) for initial load.
  - The same (or only) dark theme with `--prefersdark` so DaisyUI applies it when `prefers-color-scheme: dark`; the app already maps system light → `daylight-pastel` and system dark → `arcade-night` in JS.
- This ensures only these themes are loaded and that default/prefers-dark align with docs and generator expectations.

**Verification:** Build CSS; confirm no DaisyUI `light`/`dark` usage if we only list custom names (or keep `light`/`dark` in the list and map them in the layout if you prefer legacy names).

---

## Task 2: Migrate custom themes to `@plugin "daisyui/theme"`

**File:** `resources/css/app.css`

- Remove the existing `[data-theme="arcade-night"]`, `[data-theme="dark"]`, … blocks (and the shared `[data-theme="light"]` / `[data-theme="daylight-pastel"]` color blocks) that only set color variables.
- For each custom theme (arcade-night, daylight-pastel, retro-crt, noir-minimal, cosmic-fade):
  - Add a block using the official custom-theme format, e.g.:

    ```css
    @plugin "daisyui/theme" {
      name: "arcade-night";
      default: true;
      prefersdark: true;
      color-scheme: dark;

      /* Colors (existing values) */
      --color-primary: oklch(0.72 0.15 195);
      --color-primary-content: oklch(0.99 0 0);
      /* … rest of colors … */

      /* Radius, size, border, effects (from theme generator / docs) */
      --radius-selector: 0.25rem;
      --radius-field: 0.25rem;
      --radius-box: 0.5rem;
      --size-selector: 0.25rem;
      --size-field: 0.25rem;
      --border: 1px;
      --depth: 1;
      --noise: 0;
    }
    ```

  - Use each theme’s current color values; add `--radius-*`, `--size-*`, `--border`, `--depth`, `--noise` from the [theme generator](https://daisyui.com/theme-generator/) or [custom theme docs](https://daisyui.com/docs/themes/#how-to-add-a-new-custom-theme) so the structure matches generator output.
- Set `default: true` only for the default theme; set `prefersdark: true` only for the theme used when `prefers-color-scheme: dark` (e.g. arcade-night).
- Set `color-scheme: light` or `color-scheme: dark` per theme so browser UI (scrollbars, form controls) matches.

**Verification:** Visual check that existing pages look the same (or intentionally refined by radius/size); no missing variables.

---

## Task 3: Document theme generator workflow and optional “add theme” steps

**File:** `docs/plans/2026-03-02-daisyui-5-theming-system-integration.md` (this file) or a short `docs/theming.md`

- Add a “Adding a theme from the theme generator” section:
  1. Open [daisyUI theme generator](https://daisyui.com/theme-generator/), design the theme, then use “Add theme to your CSS file” / copy the generated block.
  2. Paste the block into `resources/css/app.css` **after** `@plugin "daisyui"` (and after any existing `@plugin "daisyui/theme"` blocks). The generator output is `@plugin "daisyui/theme" { ... }` — it should work as-is.
  3. Add the new theme name to the `themes` list in `@plugin "daisyui" { themes: ... }` (no `--default` or `--prefersdark` unless replacing the default).
  4. Add the theme to the app’s single source of truth (Task 4) so it appears in the theme picker with a label and swatch (e.g. from generator or `--color-primary`).
- Optionally: add a one-line note that generator themes can override radius/size/border/depth/noise and that our custom themes now use the same variable set for consistency.

---

## Task 4: Single source of truth for theme list (layout + head script)

**Files:**  
- New: `config/themes.php` (or a similar config / JSON), **or** keep the list in the layout but in one place only.  
- `resources/views/layouts/app.blade.php`

- Define the list of themes once: slug, label, swatch (optional), and whether it’s “light” for theme-specific overrides (e.g. hero/header). Options:
  - **Config:** e.g. `config/themes.php` returning `['arcade-night' => ['label' => 'Arcade night', 'swatch' => '#0f172a', 'light' => false], ...]`. Layout and head script read from config (Blade for layout, pass to JS via a small inline script or data attribute).
  - **Blade-only:** Single PHP array or JSON in the layout; head script reads from a global (e.g. `window.GAME_DISCOVERY_THEMES`) set by the same Blade data.
- In the layout:
  - **Head script:** Remove the hardcoded `THEMES` array and `resolveThemeSlug`. Use the same theme list to validate stored theme and to resolve `system`/`light`/`dark` (e.g. first “dark” theme for default, first “light” for prefers-light). Set `data-theme` as now.
  - **Alpine `themeToggle()`:** Remove the hardcoded `themes` array. Initialize from the same source (e.g. `x-data="themeToggle({{ json_encode(config('themes')) }})"` or from `window.GAME_DISCOVERY_THEMES`).
- When adding a theme from the generator, the only place to add the new entry is this single list (plus CSS as in Task 3).

**Verification:** Add a temporary theme in config; it appears in the picker and is applied when selected; system/light/dark resolution still works.

---

## Task 5: Align Tailwind `dark:` with DaisyUI dark themes

**File:** `resources/css/app.css`

- Today: `@custom-variant dark (&:where(.dark, .dark *));` — `dark:` is tied to class `.dark`, not to `data-theme`.
- Per [daisyUI docs](https://daisyui.com/docs/themes/#how-to-apply-tailwinds-dark-selector-for-specific-themes), add a variant that matches our dark theme name(s), e.g.:

  ```css
  @custom-variant dark (&:where([data-theme=arcade-night], [data-theme=arcade-night] *));
  ```

  If we have multiple dark themes (arcade-night, retro-crt, noir-minimal, cosmic-fade), either:
  - List them: `(&:where([data-theme=arcade-night], [data-theme=arcade-night] *, [data-theme=retro-crt], ...))`, or
  - Keep a single “default dark” (e.g. arcade-night) for `dark:` and use `[data-theme="..."]` only where a specific dark theme needs different styling.
- Use this so that theme-specific overrides (e.g. hero background, header bar) can use `dark:` where possible instead of enumerating `[data-theme="arcade-night"]`, `[data-theme="retro-crt"]`, … .

**Verification:** Use `dark:bg-...` or similar on a test element; switch to a dark theme and confirm it applies; switch to a light theme and confirm it doesn’t.

---

## Task 6: Simplify theme-specific overrides (hero, header) with a light/dark convention

**File:** `resources/css/app.css`

- Current overrides use long selectors like `[data-theme="light"] .homepage-full-bg-layer`, `[data-theme="daylight-pastel"] .header-bar`, etc.
- Introduce a convention so we don’t add every new theme name to these selectors:
  - **Option A:** Use `color-scheme` — override only when `color-scheme: light` (e.g. `html:where([data-theme][style*="color-scheme: light"])` is fragile; not ideal).
  - **Option B:** Add a data attribute when a “light” theme is active, e.g. `<html data-theme="daylight-pastel" data-theme-type="light">`. The layout or a tiny script sets `data-theme-type` from the same source of truth that defines “light” (Task 4). CSS then uses `[data-theme-type="light"] .homepage-full-bg-layer` and `[data-theme-type="light"] .header-bar` (and keep `dark:` for dark overrides where applicable).
  - **Option C:** Keep a short list of light theme slugs in CSS (e.g. a single rule with `:where([data-theme="light"], [data-theme="daylight-pastel"])`) and document that generator-imported light themes must be added to that list.
- Apply the chosen convention to:
  - `.homepage-full-bg-layer` and `::before`
  - `.header-bar` and its children (logo, nav, theme toggle)
- Remove duplicate selectors so each override appears once.

**Verification:** Switch between light and dark themes; hero and header styles match current behavior and any new “light” theme from the generator is covered by the convention.

---

## Task 7: Preserve or adapt app-specific theme tokens

**File:** `resources/css/app.css`

- The app uses `@theme { --font-sans: ...; --font-display: ...; --color-accent: ...; --color-accent-foreground: ...; }`. These are Tailwind theme extensions, not DaisyUI theme variables.
- Ensure they don’t conflict: DaisyUI themes set semantic colors (e.g. `--color-primary`); our `@theme` sets fonts and possibly accent. If `--color-accent` in `@theme` overrides DaisyUI’s semantic accent, decide whether to rely on DaisyUI’s `--color-accent` per theme only (remove from `@theme`) or keep a global override for non-semantic use.
- Keep `@theme` for fonts and any non-DaisyUI variables; align accent with DaisyUI per-theme variables so the theme generator’s accent is respected when a theme is applied.

**Verification:** Apply a generator theme that changes accent; confirm accent updates. Confirm fonts and other app tokens still apply.

---

## Task 8: Tests and docs

- **Tests:** Add or extend a minimal test that ensures the theme list (from config or single source) is non-empty, contains expected default theme(s), and that resolving `system`/`light`/`dark` yields a valid theme slug. Optionally: browser or feature test that toggling theme updates `data-theme` and persists to localStorage.
- **Docs:** In the plan or `docs/theming.md`, summarize:
  - That we use DaisyUI 5 with `@plugin "daisyui" { themes: ... }` and `@plugin "daisyui/theme"` for custom themes.
  - That custom themes use the full variable set (colors + radius, size, border, depth, noise, color-scheme) to match the theme generator.
  - Step-by-step “add a theme from the theme generator” (Task 3).
  - Where the theme list lives and how to add a new theme to the picker (Task 4).

---

## Adding a theme from the theme generator

1. Open the [daisyUI theme generator](https://daisyui.com/theme-generator/), design the theme, then use **“Add theme to your CSS file”** and copy the generated block.
2. Paste the block into `resources/css/app.css` **after** `@plugin "daisyui"` and after any existing `@plugin "daisyui/theme"` blocks. The generator output is `@plugin "daisyui/theme" { ... }` — it should work as-is.
3. Add the new theme name to the `themes` list in `@plugin "daisyui" { themes: ... }` (no `--default` or `--prefersdark` unless you are replacing the default).
4. Add the theme to the app’s single source of truth (see Task 4) so it appears in the theme picker with a label and swatch (e.g. from the generator or from `--color-primary`).

Generator themes can override radius, size, border, depth, and noise; our custom themes use the same variable set for consistency.

---

## Summary

| Area | Change |
|------|--------|
| **Plugin config** | `@plugin "daisyui" { themes: ... }` with explicit default and prefersdark |
| **Custom themes** | Migrate to `@plugin "daisyui/theme" { ... }` with full variables (generator-compatible) |
| **Theme list** | Single source of truth (config or layout) for head script + Alpine picker |
| **dark: variant** | Map Tailwind `dark:` to DaisyUI dark theme(s) via `@custom-variant` |
| **Overrides** | Light/dark convention for hero and header so new themes don’t require CSS edits |
| **Workflow** | Document paste-from-generator flow and where to add new themes |

This brings the project in line with [daisyUI themes](https://daisyui.com/docs/themes/) and makes the [theme generator](https://daisyui.com/theme-generator/) output a direct fit for adding or tuning themes.
