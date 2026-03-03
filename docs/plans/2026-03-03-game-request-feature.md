# Game Request Feature Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let authenticated users request games to be added to the database from the homepage; store requests; process the most-requested titles automatically via a scheduled or manually triggered job; and provide a dynamic, realtime admin page to manage requests and run the processor.

**Architecture:** (1) **User-facing:** A card under the hero on the welcome page (auth only) with a short message and a single text field (game title). Submissions create or update a normalized “request” and record the user’s vote so one user = one vote per title. (2) **Data:** `game_requests` table (normalized_title, display_title, request_count, status, game_id, added_at) and `game_request_votes` (user_id, game_request_id) to enforce one vote per user per title and to derive request_count. (3) **Processing:** A queued job (and an Artisan command that dispatches it) loads pending requests ordered by request_count desc, skips titles already in DB or already marked added, uses existing `GameDataProviderResolver` to search by title (e.g. RAWG first), picks first/best result, dispatches `SyncGameJob`, then marks the request as added and links `game_id`. Progress is written to Cache (run_id) for realtime admin UI. (4) **Admin:** New page “Game requests” with list of requests (pending/added), “Run processor” button, and live progress (wire:poll + JSON progress endpoint) similar to news enrichment.

**Tech Stack:** Laravel 12, Livewire (anonymous welcome component + new request card component; admin Livewire for list + progress), Blade, Tailwind v4, DaisyUI 5 (user frontend), Flux (admin). Existing: `GameDataProviderResolver`, `SyncGameJob`, RAWG/IGDB providers.

---

## Out of scope / assumptions

- No merging of multiple provider results for one request; use one provider (e.g. RAWG) per run for simplicity, or try RAWG then IGDB until one returns a result.
- “Most requested” = order by `request_count` desc; process top N per run (e.g. 5 or 10) to avoid long runs.
- Realtime admin = polling every 2s when a run is active; progress stored in Cache by run_id.
- Do not add a game that already exists (match by existing `Game` search or by external_id/external_source after search); do not process requests already marked `added` or linked to a `game_id`.

---

## Task 1: Migrations for game_requests and game_request_votes

**Files:**
- Create: `database/migrations/xxxx_create_game_requests_table.php`
- Create: `database/migrations/xxxx_create_game_request_votes_table.php`

**Step 1: Create game_requests migration**

Run: `php artisan make:migration create_game_requests_table --no-interaction`.

In `up()`: create table `game_requests` with columns: `id` (bigIncrements), `normalized_title` (string, unique), `display_title` (string – last submitted title for display), `request_count` (unsignedInteger, default 0), `status` (string, default 'pending' – values: pending, added), `game_id` (nullable foreignId to games), `added_at` (nullable timestamp), `timestamps()`. In `down()`: drop the table.

**Step 2: Create game_request_votes migration**

Run: `php artisan make:migration create_game_request_votes_table --no-interaction`.

In `up()`: create table `game_request_votes` with `id` (bigIncrements), `game_request_id` (foreignId to game_requests, cascadeOnDelete()), `user_id` (foreignId to users, cascadeOnDelete()), `timestamps()`, and unique index on `['game_request_id', 'user_id']`. In `down()`: drop the table.

**Step 3: Run migrations**

```bash
php artisan migrate --no-interaction
```

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add game_requests and game_request_votes migrations"
```

---

## Task 2: Models GameRequest and GameRequestVote

**Files:**
- Create: `app/Models/GameRequest.php`
- Create: `app/Models/GameRequestVote.php`
- Modify: `app/Models/Game.php` (add relationship)
- Modify: `app/Models/User.php` (add relationship)

**Step 1: Create GameRequest model**

Run: `php artisan make:model GameRequest --no-interaction`.

- Fillable: `normalized_title`, `display_title`, `request_count`, `status`, `game_id`, `added_at`.
- Casts: `added_at` => `datetime` (in `casts()` method per project).
- Relationships: `belongsTo(Game::class)`, `hasMany(GameRequestVote::class)`.
- PHPDoc: id, normalized_title, display_title, request_count, status, game_id, added_at, timestamps.
- Scope: `scopePending(Builder $q)` where status is `pending` and `game_id` is null.

**Step 2: Create GameRequestVote model**

Run: `php artisan make:model GameRequestVote --no-interaction`.

- Fillable: `game_request_id`, `user_id`.
- Relationships: `belongsTo(GameRequest::class)`, `belongsTo(User::class)`.
- Add unique constraint in migration if not already (game_request_id + user_id).

**Step 3: Add relationships on Game and User**

In `Game`: `hasMany(GameRequest::class)`.
In `User`: `hasMany(GameRequestVote::class)`.

**Step 4: Commit**

```bash
git add app/Models/GameRequest.php app/Models/GameRequestVote.php app/Models/Game.php app/Models/User.php
git commit -m "feat: add GameRequest and GameRequestVote models and relationships"
```

---

## Task 3: Normalize title helper and request submission logic

**Files:**
- Create: `app/Services/GameRequestNormalizer.php` (or static method on GameRequest)
- Test: `tests/Unit/Services/GameRequestNormalizerTest.php` or `tests/Unit/Models/GameRequestTest.php`

**Step 1: Write failing test for normalized title**

In a new Pest test file, add test: given a title string, expect normalized form (lowercase, trimmed, collapse spaces). Example: `"  Elden   Ring  "` → `"elden ring"`.

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/Services/GameRequestNormalizerTest.php` (or chosen path).
Expected: FAIL (class or method missing).

**Step 3: Implement normalizer**

Create `GameRequestNormalizer` with static method `normalize(string $title): string`: trim, lowercase, replace multiple spaces with single space. Use in code that creates/updates `GameRequest` so `normalized_title` is always set from this.

**Step 4: Run test to verify it passes**

Run the same test. Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/GameRequestNormalizer.php tests/
git commit -m "feat: add game request title normalizer"
```

---

## Task 4: Form Request and submission endpoint (Livewire or controller)

**Files:**
- Create: `app/Http/Requests/StoreGameRequestRequest.php` (or inline in Livewire)
- Create: `app/Livewire/GameRequestForm.php` (or embed in welcome page as a small Livewire component)

**Step 1: Validation rules**

Rules for submitted title: `required`, `string`, `max:255`. Use array-based rules per existing Form Request convention. If using a Form Request class, authorize: user must be authenticated.

**Step 2: Submission logic (in Livewire or controller)**

- Resolve normalized title via `GameRequestNormalizer::normalize($title)`.
- Find or create `GameRequest` by `normalized_title`; set `display_title` to the submitted title (or keep first/last – prefer last for display).
- Attach vote: create `GameRequestVote` for `game_request_id` + `user_id` if not exists (firstOrCreate to avoid duplicate votes).
- Refresh `request_count` on the `GameRequest` from `votes()->count()` (or increment only when new vote).

**Step 3: Write feature test**

Test: authenticated user submits a title → one GameRequest and one GameRequestVote exist; same user submits same title again → still one vote; second user submits same title → request_count 2. Test: guest cannot submit (401/redirect).

**Step 4: Run tests**

Run: `php artisan test --compact --filter=GameRequest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/ app/Livewire/ routes/ tests/
git commit -m "feat: game request submission with validation and one vote per user per title"
```

---

## Task 5: Homepage card under hero (authenticated only)

**Files:**
- Modify: `resources/views/pages/⚡welcome.blade.php`
- Create or use: Livewire component for the request form (e.g. `game-request-card` or inline in welcome)

**Step 1: Add card section**

Place a new section **immediately after the hero section** and **before** the “Coming soon” section. Wrap in `@auth` so only authenticated users see it.

**Step 2: Card content**

- Message similar to: “You don’t find what you want? Request a game to add to the database.”
- Single input: game title (label “Game title” or “Title”).
- Submit button (e.g. “Request game”).
- Use existing DaisyUI/Tailwind conventions (card, input, btn). Match spacing (e.g. gap utilities).

**Step 3: Wire form to submission**

If using a dedicated Livewire component (recommended): mount in the card, wire:submit to submit method, show success/error message. If inline in welcome page, add the necessary Livewire logic to the anonymous component and a small form in Blade.

**Step 4: Test**

Manual: sign in, submit a title, see success; submit same title again, still one vote. Run: `php artisan test --compact tests/Feature/WelcomePageTest.php` (extend if needed for “request card visible when authenticated”).
Commit: `git add resources/views/pages/⚡welcome.blade.php` (and new Livewire view/class if any), `git commit -m "ui: add request-a-game card under hero for authenticated users"`

---

## Task 6: Game request processor service

**Files:**
- Create: `app/Services/GameRequestProcessorService.php`
- Test: `tests/Unit/Services/GameRequestProcessorServiceTest.php` or feature test

**Step 1: Define service contract**

- Method: `process(int $limit = 5, ?string $runId = null): void` (or return stats). Optional `$runId` for progress reporting.
- Load pending requests: `GameRequest::pending()->orderByDesc('request_count')->limit($limit)->get()`.
- For each: skip if a game with same title (or normalized) already exists in `games` (e.g. search by title/slug); skip if request already has `game_id` or status `added`.
- Use `GameDataProviderResolver` to resolve a provider (e.g. `rawg`); call `search($request->display_title)` (or normalized). If no results, skip or mark as “not found” (optional); if results, take first, get `external_id` and `external_source`, dispatch `SyncGameJob::dispatch($externalId, $externalSource)`.
- After sync: find the created/updated `Game` (by external_source + external_id), set on request: `game_id`, `status = 'added'`, `added_at = now()`, save.
- Write progress to Cache key `game_requests:progress:{runId}` (status, current request title, processed count, last_error, etc.) so admin can poll.

**Step 2: Check existing game before add**

Before dispatching SyncGameJob, check `Game::query()->where('external_source', $source)->where('external_id', $externalId)->exists()`; if exists, link that game to the request and mark added without dispatching again.

**Step 3: Tests**

- Mock `GameDataProviderResolver` and provider `search`; assert SyncGameJob dispatched and request updated with game_id and status.
- Assert requests already with game_id are skipped.
- Assert when search returns no results, no job dispatched and request left pending (or optionally marked).

**Step 4: Run tests and commit**

```bash
php artisan test --compact --filter=GameRequestProcessor
git add app/Services/GameRequestProcessorService.php tests/
git commit -m "feat: add GameRequestProcessorService to process most-requested games"
```

---

## Task 7: Queued job and Artisan command

**Files:**
- Create: `app/Jobs/ProcessGameRequestsJob.php`
- Create: `app/Console/Commands/ProcessGameRequestsCommand.php`

**Step 1: Job**

`ProcessGameRequestsJob` implements `ShouldQueue`. Constructor: optional `?string $runId = null`, optional `int $limit = 5`. In handle: call `GameRequestProcessorService::process($limit, $runId)` (inject service). Generate runId in job if not provided (for progress).

**Step 2: Command**

`ProcessGameRequestsCommand`: signature `game-requests:process`, options `--limit=5` (optional). Generate runId, dispatch `ProcessGameRequestsJob` with runId and limit. Output run_id so admin can pass it to progress endpoint. Command is invokable from schedule or manually.

**Step 3: Schedule (optional)**

In `routes/console.php`, add e.g. `Schedule::command('game-requests:process')->daily()->at('03:00');` (after news enrich). Document in plan.

**Step 4: Test**

Feature test: run command or dispatch job, assert job runs and progress is written to Cache for runId. Assert SyncGameJob dispatched when provider returns result.

**Step 5: Commit**

```bash
git add app/Jobs/ProcessGameRequestsJob.php app/Console/Commands/ProcessGameRequestsCommand.php routes/console.php tests/
git commit -m "feat: add ProcessGameRequestsJob and game-requests:process command"
```

---

## Task 8: Admin progress endpoint and Livewire page

**Files:**
- Create: `app/Http/Controllers/Admin/GameRequestProgressController.php`
- Modify: `routes/web.php` (admin route for progress)
- Create: `resources/views/admin/game-requests.blade.php`
- Create: Livewire component for admin game requests (e.g. `resources/views/components/⚡admin-game-requests.blade.php` and class)

**Step 1: Progress controller**

Same pattern as `NewsEnrichmentProgressController`: validate `run_id`, get Cache key `game_requests:progress:{run_id}`, return JSON or 404. Route: GET `admin/game-requests/progress` (name `admin.game-requests.progress`), middleware auth + admin.

**Step 2: Admin page view**

New view that renders layout `x-layouts.admin` and the Livewire component for game requests (list + run button + progress).

**Step 3: Livewire admin component**

- State: `runId`, `status` (idle|running|completed|failed), `progress` (array).
- List of requests: `GameRequest::query()->with('game')->orderByDesc('request_count')->get()` (or paginate). Show normalized_title, display_title, request_count, status, linked game title/link if added.
- Button “Run processor” → dispatch `ProcessGameRequestsJob` with new runId, set status = running.
- When status is running: `wire:poll.2s="refreshProgress"`; call progress endpoint with runId, update progress; when response status is completed/failed, set status and stop polling.
- Display progress: current request being processed, count added, errors (similar to news enrichment).
- Make list dynamic: after run completes, re-query or refresh so new “added” state shows without full page reload (Livewire re-render on refreshProgress when status changes).

**Step 4: Routes and nav**

In `routes/web.php`, add GET `admin/game-requests` → view `admin.game-requests`, name `admin.game-requests`; add GET `admin/game-requests/progress` → GameRequestProgressController. In admin layout header, add link “Game requests” to `route('admin.game-requests')`.

**Step 5: Tests**

Feature test: admin can access game-requests page; can trigger run and get progress JSON with valid run_id.

**Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/GameRequestProgressController.php routes/web.php resources/views/admin/ resources/views/components/ resources/views/layouts/admin.blade.php tests/
git commit -m "feat: admin game requests page with realtime progress"
```

---

## Task 9: Processor writes progress and handles run lifecycle

**Files:**
- Modify: `app/Services/GameRequestProcessorService.php`
- Modify: `app/Jobs/ProcessGameRequestsJob.php`

**Step 1: Progress structure**

At start of run: write to Cache `game_requests:progress:{runId}`: `status: 'running', processed: 0, added: 0, current_title: null, error: null`. After each request processed: update processed count, added count, current_title. On completion: set `status: 'completed'`, add summary. On exception: set `status: 'failed'`, set `error` message. Use a TTL (e.g. 1 hour) so old progress expires.

**Step 2: Job generates runId**

If runId is null in job, set `$this->runId = Str::uuid()->toString()` so progress is always addressable.

**Step 3: Manual trigger from admin**

Admin “Run processor” already dispatches job with a new runId; store that runId in Livewire state and poll progress with it. Ensure progress key is written as soon as job starts (first line in handle or in service when runId is passed).

**Step 4: Run tests and commit**

```bash
php artisan test --compact --filter=GameRequest
git add app/Services/GameRequestProcessorService.php app/Jobs/ProcessGameRequestsJob.php
git commit -m "feat: game request processor writes progress for realtime admin UI"
```

---

## Task 10: Skip requests that already have a matching game in DB

**Files:**
- Modify: `app/Services/GameRequestProcessorService.php`

**Step 1: Before searching provider**

For each pending request, check if a `Game` already exists whose title matches (e.g. normalize game title and compare to `request->normalized_title`, or use a simple `Game::query()->where('title', 'like', ...)` / slug comparison). If match found: set request’s `game_id`, `status = 'added'`, `added_at = now()`, save; do not dispatch SyncGameJob. This avoids re-adding and marks the request as fulfilled.

**Step 2: Test**

Create a Game with title “Elden Ring”; create a pending GameRequest with normalized_title “elden ring”. Run processor; assert SyncGameJob not dispatched and request is marked added with that game_id.

**Step 3: Commit**

```bash
git add app/Services/GameRequestProcessorService.php tests/
git commit -m "feat: skip game request when matching game already in database"
```

---

## Task 11: Pint and final test run

**Files:**
- All touched PHP files

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Run full test suite for feature**

```bash
php artisan test --compact tests/Feature/ tests/Unit/Services/GameRequestNormalizerTest.php tests/Unit/Services/GameRequestProcessorServiceTest.php
```

**Step 3: Commit if any style changes**

```bash
git add -A && git commit -m "style: pint"
```

---

## Summary

| Deliverable | Location |
|-------------|----------|
| DB | `game_requests`, `game_request_votes` |
| Models | `GameRequest`, `GameRequestVote`; relations on `Game`, `User` |
| Normalizer | `GameRequestNormalizer::normalize()` |
| Submission | Livewire form on homepage (auth only), card under hero |
| Processor | `GameRequestProcessorService`, `ProcessGameRequestsJob`, command `game-requests:process` |
| Admin | Page `admin/game-requests`, progress endpoint, Livewire list + run + wire:poll progress |
| Schedule | `routes/console.php`: optional daily run |

Plan complete and saved to `docs/plans/2026-03-03-game-request-feature.md`. Two execution options:

1. **Subagent-Driven (this session)** – I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel Session (separate)** – Open a new session with executing-plans and run the plan task-by-task with checkpoints.

Which approach do you prefer?
