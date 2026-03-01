# Improve News Crawling and Dashboard News Display

> **For Claude:** Use executing-plans to implement this plan task-by-task when executing.

**Goal:** (1) Fix news–game matching so that association is based on **whole words** from the game title, not substrings—e.g. news for "Death Stranding 2" must not be associated with a game titled "Strand". (2) Show a **game picture** (cover) in the news list on the user dashboard. (3) Ensure **news links open in a new tab**. (4) Add a **tooltip on hover** to show the full news title when it is truncated.

**Architecture:** Change `NewsGameMatcher` to treat the game title as a sequence of words and require that the news title contains each word as a **whole word** (word-boundary match). Keep longest-title-first ordering so "Elden Ring" still wins over "Elden" when both could match. Dashboard: add game cover image to the news sidebar list (reuse `Game::cover_image`); ensure all news article links use `target="_blank"` and `rel="noopener noreferrer"`; add `title` attribute (or equivalent) for full headline on hover where titles are truncated.

**Tech stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest. No new dependencies.

**Key files:**
- [app/Services/NewsGameMatcher.php](app/Services/NewsGameMatcher.php) – word-boundary matching logic
- [tests/Unit/Services/NewsGameMatcherTest.php](tests/Unit/Services/NewsGameMatcherTest.php) – update and add cases for word boundaries
- [resources/views/components/⚡dashboard-news-sidebar.blade.php](resources/views/components/⚡dashboard-news-sidebar.blade.php) – game cover, tooltip, new-tab link
- [resources/views/components/⚡dashboard-feed.blade.php](resources/views/components/⚡dashboard-feed.blade.php) – new-tab for news links, tooltip for full title

---

## Out of scope / assumptions

- No change to `News` or `Game` schema; use existing `cover_image` on `Game` for the picture.
- Matching remains title-only (no ML or external APIs). Word-boundary is the only matching rule change.
- Tooltip = native `title` attribute for simplicity and accessibility; no custom JS tooltip unless already in use elsewhere.

---

## Task 1: Word-boundary matching in NewsGameMatcher

**Files:**
- Modify: [app/Services/NewsGameMatcher.php](app/Services/NewsGameMatcher.php)
- Modify: [tests/Unit/Services/NewsGameMatcherTest.php](tests/Unit/Services/NewsGameMatcherTest.php)

**Step 1: Define word-boundary rule**

A game matches a news title only if **every word** in the game title appears in the news title as a **whole word** (not as part of a longer word). Examples:
- Game "Strand" → word "Strand". News "Death Stranding 2" contains "Stranding", not the word "Strand" → no match.
- Game "Death Stranding 2" → words "Death", "Stranding", "2". News "Death Stranding 2 release date" contains all three as whole words → match.
- Game "Elden Ring" → words "Elden", "Ring". News "Elden Ring DLC announced" contains both → match.

Normalize by splitting on whitespace (and optionally stripping punctuation) so "Hollow Knight" yields ["Hollow", "Knight"]. Matching is case-insensitive and whole-word only (use word boundaries: e.g. `\b` in regex, or split news title into words and check containment).

**Step 2: Implement in NewsGameMatcher**

- Extract game title words: split `$game->title` on whitespace, trim, filter empty, normalize for comparison (e.g. lowercase).
- For the news title, either: (a) use a regex with `\b` for each word, or (b) split news title into words and check that every game word appears in that set. Prefer (b) for clarity: split news title into words (same normalization), then ensure every game word is in the news word set.
- Keep existing behavior: games sorted by title length descending; first game whose words all appear as whole words in the news title wins.
- Empty game title: skip (no match). Single-word games (e.g. "Strand") must appear as that whole word in the news.

**Step 3: Update and add unit tests**

- Keep: "findMatchingGame returns game when title appears in news title" (e.g. "Elden Ring" in "Elden Ring DLC announced") — should still pass with word-boundary.
- Keep: "findMatchingGame returns null when no game title matches".
- Keep: "longest matching title wins" (e.g. "Elden Ring" vs "Ring").
- Add: "findMatchingGame does not match when game title is substring of a word in news" — e.g. game "Strand", news "Death Stranding 2" → null.
- Add: "findMatchingGame matches when all game words appear as whole words in news" — e.g. game "Death Stranding 2", news "Death Stranding 2 release date" → match.
- Add: "findMatchingGame is case-insensitive" — e.g. game "elden ring", news "Elden Ring DLC" → match (if such a game exists).

**Step 4: Run tests**

```bash
php artisan test --compact tests/Unit/Services/NewsGameMatcherTest.php
```

**Step 5: Commit**

```bash
git add app/Services/NewsGameMatcher.php tests/Unit/Services/NewsGameMatcherTest.php
git commit -m "fix: match news to games by whole words only, not substrings"
```

---

## Task 2: Game picture in dashboard news list

**Files:**
- Modify: [resources/views/components/⚡dashboard-news-sidebar.blade.php](resources/views/components/⚡dashboard-news-sidebar.blade.php)

**Step 1: Add game cover to sidebar items**

The "Latest news" sidebar currently shows: news title (link), game name, date. It does **not** show the game cover. The feed in `⚡dashboard-feed.blade.php` already shows `$item['game']->cover_image` in a small box.

In the sidebar list item:
- Add a small thumbnail (e.g. same pattern as feed: fixed size, rounded, object-cover) using `$item->game->cover_image`. If no cover, show a placeholder (e.g. first letter of game title or a neutral icon) so layout is consistent.
- Layout: e.g. flex row with image on the left (shrink-0), then title + meta on the right, so the list remains scannable and matches the feed’s use of game art.

**Step 2: Commit**

```bash
git add resources/views/components/⚡dashboard-news-sidebar.blade.php
git commit -m "feat: show game cover in dashboard news sidebar"
```

---

## Task 3: Open news in new tab

**Files:**
- [resources/views/components/⚡dashboard-news-sidebar.blade.php](resources/views/components/⚡dashboard-news-sidebar.blade.php) – already has `target="_blank"` and `rel="noopener noreferrer"` on the news link; verify and leave as-is.
- Modify: [resources/views/components/⚡dashboard-feed.blade.php](resources/views/components/⚡dashboard-feed.blade.php)

**Step 1: Verify sidebar**

Confirm the news link in the sidebar uses `target="_blank"` and `rel="noopener noreferrer"`. No change if already present.

**Step 2: Feed news link**

In the "Recent updates" feed, the "Read article" link (when `$item['type'] === 'new_article'`) points to `$item['url']`. Add `target="_blank"` and `rel="noopener noreferrer"` to that link so clicking opens the article in a new tab.

**Step 3: Commit**

```bash
git add resources/views/components/⚡dashboard-feed.blade.php
git commit -m "feat: open news article links in new tab from dashboard feed"
```

---

## Task 4: Tooltip for full title on hover

**Files:**
- Modify: [resources/views/components/⚡dashboard-news-sidebar.blade.php](resources/views/components/⚡dashboard-news-sidebar.blade.php)
- Modify: [resources/views/components/⚡dashboard-feed.blade.php](resources/views/components/⚡dashboard-feed.blade.php)

**Step 1: Sidebar**

The news title is rendered with `line-clamp-2`, so long titles are truncated. Add a `title` attribute to the element that displays the news title (or the wrapping link) with the full `$item->title` so hovering shows the full headline in the browser tooltip.

**Step 2: Feed**

The feed shows `$item['title']` in a `<p>`. If that text is ever truncated (e.g. via line-clamp or overflow), add a `title` attribute with the full title. If there is no truncation in the current design, still add `title="{{ $item['title'] }}"` on the title element or the "Read article" link for consistency when users hover.

**Step 3: Commit**

```bash
git add resources/views/components/⚡dashboard-news-sidebar.blade.php resources/views/components/⚡dashboard-feed.blade.php
git commit -m "feat: tooltip with full news title on hover in dashboard"
```

---

## Verification

- Run `php artisan test --compact` for NewsGameMatcher and any feature tests that touch news or dashboard.
- Manually: run news enrichment (or use existing news), confirm "Strand" no longer gets "Death Stranding 2" articles; confirm dashboard sidebar and feed show game cover, new-tab behavior, and full title on hover.
