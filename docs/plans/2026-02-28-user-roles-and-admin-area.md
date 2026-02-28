# User Roles and Admin Area Implementation Plan

> **For Claude:** Use superpowers:executing-plans to implement this plan task-by-task when executing.

**Goal:** Add a role to the existing User model (regular user vs admin), enforce admin-only access via a gate and middleware, and introduce an admin area: dedicated layout and dashboard page where administrator actions will be gathered (e.g. future: update DB, manage games).

**Architecture:** Single `User` model with a `role` attribute (enum: `User`, `Admin`). Authorization via Laravel Gate `accessAdmin`; admin routes live under `/admin` with middleware `auth`, `verified`, and a custom `EnsureUserIsAdmin` (or equivalent) middleware. Admin UI uses its own layout (`layouts/admin.blade.php`) and a simple dashboard view; main app header shows an “Admin” link only for admin users.

**Tech Stack:** Laravel 12, existing Livewire + Flux + Tailwind v4, Pest. No new dependencies.

---

## Task 1: UserRole enum

**Files:**
- Create: `app/Enums/UserRole.php`

**Step 1: Create enum**

```bash
php artisan make:enum UserRole --no-interaction
```

**Step 2: Define cases**

In `app/Enums/UserRole.php`, add backed enum with string values:

- `User` → `'user'` (default for existing users)
- `Admin` → `'admin'`

Follow project convention (TitleCase keys). Use `enum UserRole: string` with matching case values.

**Step 3: Commit**

```bash
git add app/Enums/UserRole.php
git commit -m "feat: add UserRole enum"
```

---

## Task 2: Add role column to users table

**Files:**
- Create: `database/migrations/xxxx_add_role_to_users_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration add_role_to_users_table --table=users --no-interaction
```

**Step 2: Define schema**

In the migration: add column `role` (string, default `'user'`). No need to make it nullable if default is set.

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 4: Commit**

```bash
git add database/migrations/*_add_role_to_users_table.php
git commit -m "feat: add role column to users table"
```

---

## Task 3: User model role and helper

**Files:**
- Modify: `app/Models/User.php`

**Step 1: Cast role**

In `User`, add `role` to the `casts()` array as `UserRole::class` (or equivalent enum cast). Add `@property-read` for `role` in the class docblock if using PHPDoc.

**Step 2: Default role**

Ensure new users get default role: in migration the column has default `'user'`. Optionally in `User` set `$attributes['role'] = UserRole::User` for model default (redundant if migration default is set).

**Step 3: isAdmin() helper**

Add a method `isAdmin(): bool` that returns `$this->role === UserRole::Admin`. Use this (and/or the gate) for blade conditionals and policies.

**Step 4: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: cast User role and add isAdmin()"
```

---

## Task 4: Gate and middleware for admin access

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `app/Http/Middleware/EnsureUserIsAdmin.php`

**Step 1: Define gate**

In `AppServiceProvider::boot()`: register a gate named `accessAdmin` so that only users with `UserRole::Admin` can access the admin area. Example: `Gate::define('accessAdmin', fn (User $user) => $user->role === UserRole::Admin);`. Use the `User` and `UserRole` imports.

**Step 2: Create middleware**

```bash
php artisan make:middleware EnsureUserIsAdmin --no-interaction
```

Implement: if `auth()->guest()` redirect to login; if `! auth()->user()->isAdmin()` abort 403 (or redirect with message). Otherwise `$next($request)`.

**Step 3: Register middleware alias**

In `bootstrap/app.php`, inside `withMiddleware()`, register an alias so routes can use `'admin'` (e.g. `$middleware->alias(['admin' => \App\Http\Middleware\EnsureUserIsAdmin::class]);`). Laravel 12 uses `Application::configure()->withMiddleware()`.

**Step 4: Commit**

```bash
git add app/Providers/AppServiceProvider.php app/Http/Middleware/EnsureUserIsAdmin.php bootstrap/app.php
git commit -m "feat: gate and middleware for admin access"
```

---

## Task 5: Admin routes and placeholder dashboard

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Admin/DashboardController.php` (or single controller under `Admin` namespace)
- Create: `resources/views/admin/dashboard.blade.php`

**Step 1: Admin route group**

In `routes/web.php`, add a route group: prefix `admin`, name prefix `admin.`, middleware `['auth', 'verified', 'admin']`. Inside, add `Route::get('/', [Admin DashboardController::class, '__invoke'])->name('dashboard');` (so full route name is `admin.dashboard`, path `/admin`).

**Step 2: Create admin dashboard controller**

```bash
php artisan make:controller Admin/DashboardController --invokable --no-interaction
```

In the controller: return view `admin.dashboard`. No extra data required for now.

**Step 3: Create admin dashboard view**

Create `resources/views/admin/dashboard.blade.php`. Use the admin layout (created in next task) via `<x-layouts.admin title="Admin">` … `</x-layouts.admin>` (slot content). Content: a simple heading (e.g. “Admin”) and a short paragraph that administrator actions (e.g. update DB, manage content) will be gathered here. No sidebar links needed yet beyond “Dashboard” if you add a minimal nav in the layout.

**Step 4: Commit**

```bash
git add routes/web.php app/Http/Controllers/Admin/DashboardController.php resources/views/admin/dashboard.blade.php
git commit -m "feat: admin routes and placeholder dashboard"
```

---

## Task 6: Admin layout

**Files:**
- Create: `resources/views/layouts/admin.blade.php`

**Step 1: Base structure**

Create a layout that mirrors the main app layout (same head, Vite, Livewire, Flux, Tailwind, fonts) so styling is consistent. Body can use the same wrapper and appearance switcher if desired.

**Step 2: Admin-specific header**

Header should include: link back to main site (e.g. “← Back to site” or app name linking to `/`), “Dashboard” link to `route('admin.dashboard')`, and user/logout (reuse same pattern as main layout for auth). No game search modal required in admin layout unless you want it. Keep it minimal so future admin actions can be added as links or in-dashboard cards.

**Step 3: Slot for content**

Use the same component pattern as the main app: `@props(['title' => null])` and `{{ $slot }}` so admin views use `<x-layouts.admin :title="...">` with slot content (see `resources/views/layouts/app.blade.php`).

**Step 4: Commit**

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "feat: admin layout"
```

---

## Task 7: Link to admin in main app header

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Conditional Admin link**

Inside the `@auth` block, add a link to `route('admin.dashboard')` with text “Admin”, only if `auth()->user()->isAdmin()`. Place it next to the existing Dashboard link (e.g. after “Dashboard”, before profile name).

**Step 2: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: show Admin link in header for admins"
```

---

## Task 8: User factory and tests

**Files:**
- Modify: `database/factories/UserFactory.php`
- Create or modify: `tests/Feature/AdminAccessTest.php` (or `AdminAreaTest.php`)
- Optional: `tests/Unit/Models/UserTest.php` for `isAdmin()`

**Step 1: User factory role**

In `UserFactory`, add `'role' => UserRole::User` in the definition. Add a state `admin()` that sets `'role' => UserRole::Admin` so tests can use `User::factory()->admin()->create()`.

**Step 2: Feature test – admin area**

In `tests/Feature/AdminAccessTest.php`: (1) Guest visiting `/admin` is redirected to login. (2) Authenticated non-admin user visiting `/admin` receives 403. (3) Authenticated admin user visiting `/admin` gets 200 and sees the admin dashboard content (e.g. “Admin” or “administrator actions”). Use `User::factory()->admin()->create()` and `User::factory()->create()` for the two auth cases.

**Step 3: Unit test – User role (optional)**

In `tests/Unit/Models/UserTest.php`: test that a user with `role => UserRole::Admin` has `isAdmin()` true, and with `role => UserRole::User` has `isAdmin()` false. Use RefreshDatabase and the factory with state.

**Step 4: Run tests**

```bash
php artisan test --compact tests/Feature/AdminAccessTest.php tests/Unit/Models/UserTest.php
```

**Step 5: Commit**

```bash
git add database/factories/UserFactory.php tests/Feature/AdminAccessTest.php tests/Unit/Models/UserTest.php
git commit -m "test: admin access and User role"
```

---

## Task 9: Run full test suite and Pint

**Step 1: Run tests**

```bash
php artisan test --compact
```

Fix any failing tests.

**Step 2: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 3: Commit**

```bash
git add -A && git status
git commit -m "chore: style and fix tests for user roles and admin area"
```

---

## Execution handoff

Plan saved to `docs/plans/2026-02-28-user-roles-and-admin-area.md`.

**Next steps:** Execute task-by-task (e.g. with executing-plans skill), or implement manually following the order above. After execution, you can promote the first user to admin via tinker: `User::first()?->update(['role' => \App\Enums\UserRole::Admin]);` or a one-off seeder.
