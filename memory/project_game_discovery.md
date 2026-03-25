---
name: Game Discovery — project status
description: Current state of the Game Discovery Laravel app and what's been improved
type: project
---

Laravel 12 app for tracking upcoming game releases, news, and backlog planning. In production via Docker (container: game-discovery-app).

**Already implemented:** Auth (Fortify + 2FA), game tracking, dashboard, admin panel (RAWG/IGDB import, news enrichment, game requests), GameActivity recording.

**Improved in March 2026 session:**
- Removed duplicate auth dropdown from navbar (kept simple Sign in link)
- Authenticated users redirected from `/` to `/dashboard`
- Removed hardcoded fake backlog data from homepage (Elden Ring/BG3/Cyberpunk)
- Added `⚡welcome-news` component to homepage (real news feed)
- All auth forms converted from Zinc classes to DaisyUI (input, btn-primary, text-error, etc.)
- Public game catalogue at `/games` (Livewire, search + filter, pagination, URL params)
- "Games" link added to nav and footer for all users
- Dashboard feed (`⚡dashboard-feed`) now shown in sidebar — replaces news-only sidebar, shows news + GameActivities unified
- Game detail page (`games/show.blade.php`) fully converted to DaisyUI + activity timeline section added
- `GameActivityFactory` created with `releaseDateChanged()` and `gameReleased()` states

**Still to do (from improvement plan):**
- Logo/app name (currently "S" placeholder, APP_NAME not set)
- Profile page style consistency check
- Backlog planning feature (time tracking not yet implemented)

**Why:** App is live in prod but was incomplete. Working through a cleanup/feature plan session by session.

**How to apply:** When suggesting next improvements, pick from the "still to do" list or ask the user.
