# Homepage (Gaming Tracker Preview) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the current welcome page with a product-preview homepage that demonstrates value in under 10 seconds: track upcoming releases, follow news, estimate backlog time, and when the user will realistically play games—using example data only, no auth required.

**Architecture:** Keep the welcome page as a single-file Livewire component at `resources/views/pages/⚡welcome.blade.php` (no new Livewire classes). All sections use real `Game`/`News` data where applicable; backlog and “when will you play” use example/static data (no `time_to_beat` on Game yet). Top nav and footer live in `resources/views/layouts/app.blade.php`. Section order: Hero → Upcoming releases → Backlog planning → Game news → Playable-date insight → Final CTA; add footer to layout.

**Tech Stack:** Laravel 12, Livewire (single-file components), Tailwind v4, DaisyUI 5, Alpine.js (modals/panels). No new dependencies.

---

## Task 1: Top navigation — Features, How it works, Sign in, Start tracking

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (nav and mobile nav)

**Step 1: Update desktop nav items**

In `resources/views/layouts/app.blade.php`, in the `<nav class="hidden items-center gap-6 md:flex">` block (around lines 59–64), replace the current links with:
- **Features** → `href="{{ url('/') }}#features"` (anchor)
- **How it works** → `href="{{ url('/') }}#how-it-works"`
- **Sign in** → `href="{{ url('/login') }}"` (or `route('login')` if named)
- Keep search and theme; for guest users replace the Register link with a **primary CTA** button: "Start tracking" → `href="{{ url('/register') }}"` with classes so it stands out (e.g. `btn btn-primary`), and ensure "Sign in" is a text link.

**Step 2: Update mobile nav**

In the same file, in the mobile menu (around lines 212–216), use the same four items: Features, How it works, Sign in, Start tracking (with Start tracking as primary button style).

**Step 3: Verify**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS (no nav assertions yet).

**Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat(homepage): nav items Features, How it works, Sign in, Start tracking"
```

---

## Task 2: Hero section — two-column, headline, description, CTAs, dashboard preview

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`

**Step 1: Write failing test**

In `tests/Feature/WelcomePageTest.php`, add:

```php
test('welcome page hero shows product headline and primary CTA', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Track your games.', false);
    $response->assertSee('Know when you\'ll actually play them.', false);
    $response->assertSee('Start tracking your games', false);
    $response->assertSee('See how it works', false);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php --filter="hero shows product headline"`
Expected: FAIL (old hero copy).

**Step 3: Replace hero section in welcome view**

In `resources/views/pages/⚡welcome.blade.php`, replace the existing hero `<section>` (lines ~72–113) with a two-column hero:

- **Left column:** Headline "Track your games." (line 1) and "Know when you'll actually play them." (line 2); short description per spec; primary button "Start tracking your games" → `url('/register')`; secondary link "See how it works" → `#how-it-works`.
- **Right column:** Visual preview — use a placeholder or existing `x-game.hero-tile` for the first upcoming/popular game as “dashboard preview”, or a static image asset (e.g. `resources/images/dashboard-preview.png`) if available; otherwise a composed mini mock (e.g. small cards for “upcoming / backlog / news”) so the product structure is visible. Prefer reusing one hero game tile for simplicity in V1.

**Step 4: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: New test passes; fix any regressions (e.g. update "Discover your next game" assertion if removed).

**Step 5: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): hero section with product headline and CTAs"
```

---

## Task 3: Section — Upcoming releases preview (title, description, 4–6 cards, countdown, news count)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Modify: `resources/views/components/game/card.blade.php` or create `resources/views/components/game/upcoming-card.blade.php` (optional)

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page shows upcoming releases section with countdown and news', function (): void {
    $game = Game::factory()->create([
        'title' => 'Silksong Demo',
        'release_date' => now()->addDays(120),
    ]);
    News::factory()->create(['game_id' => $game->id, 'title' => 'New trailer']);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Upcoming releases', false);
    $response->assertSee('Track the games you\'re waiting for', false);
    $response->assertSee('Silksong Demo', false);
    $response->assertSee('Track your first game', false);
});
```

**Step 2: Run test (fail)**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php --filter="upcoming releases section"`
Expected: FAIL (section title/copy may exist but countdown/news count not yet).

**Step 3: Implement upcoming releases section**

- In `⚡welcome.blade.php`, add or rename a section with `id="upcoming-releases"` (and optionally `id="features"` on a wrapper or first feature section for nav). Title: "Upcoming releases"; description: "Track the games you're waiting for and see exactly how long until they release."
- Reuse `getUpcomingGames()` but limit to 4–6 (e.g. `->limit(6)`). For each game, show: cover (existing), title, release date, countdown (e.g. "X days" via Carbon diff), and news count (e.g. `$game->news()->count()` or eager load `withCount('news')` in the component). Use `x-ui.card-row` or a small grid of cards; card can be `x-game.card` with a custom status slot showing countdown + "N news" or a new Blade component `x-game.upcoming-card` that accepts `$game` and shows cover, title, release date, countdown, news count.
- Add CTA link/button below: "Track your first game" → `#` or `url('/register')`.

**Step 4: Ensure query has withCount('news')**

In the welcome component's `getUpcomingGames()`, add `->withCount('news')` so the view can show news count without N+1.

**Step 5: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 6: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php resources/views/components/game/upcoming-card.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): upcoming releases section with countdown and news count"
```

---

## Task 4: Game card click — preview panel/modal (completion time, latest news, release info)

**Files:**
- Create: `resources/views/components/home/game-preview-panel.blade.php` (Alpine + slot content)
- Modify: `resources/views/pages/⚡welcome.blade.php` or the upcoming card

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page upcoming section has game links or preview triggers', function (): void {
    Game::factory()->create(['title' => 'Preview Game', 'release_date' => now()->addDays(30)]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Preview Game', false);
    // Either link to game show or data attribute for preview
    expect($response->getContent())->toContain('Preview Game');
});
```

**Step 2: Implement preview panel**

- Create a Blade component (or inline Alpine in welcome) that shows a slide-over panel or modal when a game card is clicked. Content: estimated completion time (placeholder "—" or "N/A" until `time_to_beat` exists), latest news headline (first of `$game->news()->latest('published_at')->first()`), release date and countdown. Use Alpine `x-data`, `x-show`, `@click` on the card; pass game data via `x-init` or a small Livewire wire:click that sets selected game (simplest: Alpine-only with JSON in data attribute).
- In the upcoming section, wrap each game card so click opens this panel (and optionally link to `route('games.show', $game)` on "View game" inside the panel).

**Step 3: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 4: Commit**

```bash
git add resources/views/components/home/game-preview-panel.blade.php resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): game preview panel on upcoming card click"
```

---

## Task 5: Section — Backlog planning (sample list, estimated hours, total)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page shows backlog planning section with example items', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Plan your gaming backlog', false);
    $response->assertSee('See how long your games take', false);
    $response->assertSee('Total backlog time', false);
    $response->assertSee('Plan your backlog', false);
});
```

**Step 2: Run test (fail)**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php --filter="backlog planning"`
Expected: FAIL.

**Step 3: Implement backlog section**

- Add section "Plan your gaming backlog" with description "See how long your games take to finish and estimate the total time needed to complete your backlog."
- Use **example data**: 3–4 games with hardcoded "estimated time to beat" (e.g. Elden Ring — ~60 hours, Baldur's Gate 3 — ~80 hours, Cyberpunk 2077 — ~25 hours). Resolve games by title/slug from DB if they exist, otherwise use static labels and placeholder cover or first available game covers. Sum to "Total backlog time: 165 hours" (or computed).
- List items: game title, cover image, estimated time. Below: "Total backlog time" + sum. CTA: "Plan your backlog" → register or #how-it-works.
- Implement as a method on the welcome component, e.g. `getSampleBacklogItems()` returning a collection of `['game' => Game|null, 'hours' => int]` (games from factory or `Game::whereIn('slug', ['elden-ring', ...])->get()` keyed by slug; fallback to minimal DTO with title and cover_image).

**Step 4: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): backlog planning section with sample list and total time"
```

---

## Task 6: Section — Game news tracking (title, feed 4–5 items, CTA)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Modify: `resources/views/components/⚡welcome-news.blade.php` (optional: accept limit prop)

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page shows stay updated news section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Stay updated on your games', false);
    $response->assertSee('Follow your games', false);
});
```

**Step 2: Implement section**

- Add section "Stay updated on your games" with description "Automatically receive the latest news for the games you track." Limit feed to 4–5 items: either pass a limit to the existing `livewire:welcome-news` (if refactored to accept `limit`) or wrap the same component and hide items after the 5th with CSS or a new prop. Prefer constraining the query in the WelcomeNews component when used on homepage (e.g. `limit(5)` for initial load). CTA: "Follow your games" → register.
- Ensure each news item shows: game title, headline (news title), source, time (e.g. "2 days ago") — already in `⚡welcome-news.blade.php`; adjust labels if needed.

**Step 3: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 4: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php resources/views/components/⚡welcome-news.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): game news section with title and follow CTA"
```

---

## Task 7: Section — When will you actually play it? (comparison table, CTA)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page shows playable date insight section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('When will you actually play it?', false);
    $response->assertSee('Your backlog determines when you\'ll start new games.', false);
    $response->assertSee('Calculate your backlog', false);
});
```

**Step 2: Implement section**

- Add section with `id="how-it-works"` (or a dedicated id and keep "How it works" nav pointing to a short step list above/below). Title: "When will you actually play it?" Description: "Your backlog determines when you'll start new games."
- Add a small comparison table (static example): columns Game, Release date, Backlog remaining, Estimated playable date. One example row: e.g. Silksong, Release in 120 days, Backlog: 90 hours, Playable in about 2 weeks. Use a `<table>` or DaisyUI table classes. CTA: "Calculate your backlog" → register.

**Step 3: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 4: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): when will you play it section with example table"
```

---

## Task 8: Final CTA section and content order

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php`:

```php
test('welcome page shows final CTA section', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Track your games and plan your backlog.', false);
});
```

**Step 2: Enforce section order and add final CTA**

- Ensure section order is: Hero → Upcoming releases → Backlog planning → Game news → When will you play it → Final CTA. Remove or relocate any existing sections that don’t match the spec (e.g. "Trending now", "Recently released", "Your backlog" if they duplicate or conflict; spec says 4–6 items per section and no personalization—so drop user-specific "Your backlog" for guests or keep it as "after login" teaser). Per spec, prefer a single flow: Hero, Upcoming, Backlog, News, Playable date, Final CTA.
- Add final CTA section: short message "Track your games and plan your backlog." Primary button "Start tracking your games" → register. Secondary link "Sign in".
- Add `id="features"` to the first feature block (e.g. Upcoming releases) so "Features" in nav has a target.

**Step 3: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 4: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): section order and final CTA block"
```

---

## Task 9: Footer (product name, description, links)

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Add test**

In `tests/Feature/WelcomePageTest.php` (or a new test file):

```php
test('layout footer contains product name and key links', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee(config('app.name'), false);
    $response->assertSee('Features', false);
    $response->assertSee('How it works', false);
});
```

**Step 2: Add footer to layout**

In `resources/views/layouts/app.blade.php`, after `<main class="grow">` and before the closing `</div>` of the main wrapper, add a `<footer>`. Content: product name (link to `/`), short app description, links: Features (`#features`), How it works (`#how-it-works`), Privacy (route or URL if exists), Terms (route or URL if exists), and optional social links. Use Tailwind/DaisyUI for a simple horizontal or stacked footer.

**Step 3: Create placeholder routes for Privacy/Terms if missing**

If the app has no privacy/terms pages, add `Route::view('/privacy', 'pages.privacy')->name('privacy');` and similar for terms (or a single "Legal" page), and create minimal Blade views; or link to `#` with "Privacy" / "Terms" for now.

**Step 4: Run test**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php routes/web.php tests/Feature/WelcomePageTest.php
git commit -m "feat(homepage): footer with product name, description, and links"
```

---

## Task 10: Welcome page title and existing test updates

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Modify: `tests/Feature/WelcomePageTest.php`

**Step 1: Set page title**

In the welcome component class, set `#[Title('Track your games')]` (or similar) so the browser tab matches the product.

**Step 2: Update existing tests**

Review all tests in `WelcomePageTest.php`. Change or remove assertions that reference old copy (e.g. "Discover your next game", "Explore games", "Trending now", "Coming soon" vs "Upcoming releases") so they align with the new sections and CTAs. Ensure no duplicate or conflicting expectations.

**Step 3: Run full welcome test file**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php`
Expected: All PASS.

**Step 4: Commit**

```bash
git add resources/views/pages/⚡welcome.blade.php tests/Feature/WelcomePageTest.php
git commit -m "chore(homepage): update title and welcome tests for new copy"
```

---

## Task 11: Pint and final smoke test

**Files:**
- N/A (run commands)

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty`

**Step 2: Run welcome and browser tests**

Run: `php artisan test --compact tests/Feature/WelcomePageTest.php tests/Browser/WelcomeTest.php`
Expected: All PASS. Fix any Browser test expectations (e.g. "Discover your next game" → new headline) if present.

**Step 3: Commit**

```bash
git add -A
git commit -m "style: pint"
```
(Only if Pint changed files.)

---

## Out of scope / assumptions

- **Authentication:** Login/Register routes exist (e.g. Breeze); use `url('/login')` and `url('/register')` or named routes if available.
- **Time to beat:** No migration for `Game.time_to_beat` in this plan; backlog and preview use example/static hours. A follow-up can add the column and real data.
- **Dashboard preview image:** Hero right side can be a single game tile or a static image; a polished dashboard screenshot can be added later.
- **Features / How it works:** Implemented as anchor links to sections (#features, #how-it-works); no separate pages unless product already has them.
- **Max items:** Upcoming 4–6, backlog 3–4, news 4–5 per spec.

---

## Key files reference

| Purpose | Path |
|--------|------|
| Welcome page (Livewire) | `resources/views/pages/⚡welcome.blade.php` |
| App layout (nav, footer) | `resources/views/layouts/app.blade.php` |
| Game card | `resources/views/components/game/card.blade.php` |
| Section header | `resources/views/components/ui/section-header.blade.php` |
| Card row (horizontal scroll) | `resources/views/components/ui/card-row.blade.php` |
| Welcome news | `resources/views/components/⚡welcome-news.blade.php` |
| Routes | `routes/web.php` |
| Welcome tests | `tests/Feature/WelcomePageTest.php` |
