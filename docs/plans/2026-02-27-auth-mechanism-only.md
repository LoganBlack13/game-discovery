# Auth (Mechanism Only) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add authentication (registration, login, password reset, email verification, optional 2FA, username, avatar) using only Laravel Fortify as the backend. No Jetstream, no starter-kit UI. All views and Livewire components are built in-house using the existing Livewire 4 + Flux layout and config.

**Architecture:** Install `laravel/fortify` only. Run `fortify:install` to get config, provider, and actions; do not use any published views or overwrite layout. Register Fortify view callbacks to point to our own Blade views that extend `layouts.app`. Build minimal Blade views (or Livewire where desired, e.g. login dropdown) that POST to Fortify’s routes. Add `username` and profile/avatar/2FA via our own migrations and Fortify action overrides. No Jetstream, no Sanctum (unless API tokens are required later).

**Tech Stack:** Laravel 12, Livewire 4, Flux, Tailwind v4, Pest. Auth: Fortify only.

---

## Task 1: Install Fortify and publish only mechanism

**Files:**
- Modify: `composer.json` (add `laravel/fortify`)
- Create: `config/fortify.php` (from publish)
- Create: `app/Providers/FortifyServiceProvider.php` (from publish)
- Modify: `bootstrap/providers.php` (register FortifyServiceProvider)
- Create: `app/Actions/Fortify/*` (from `fortify:install`)

**Step 1: Require Fortify**

```bash
composer require laravel/fortify --no-interaction
```

**Step 2: Run Fortify install (publishes config, provider, actions, migrations)**

```bash
php artisan fortify:install
```

**Step 3: Review published artifacts**

- In `config/fortify.php`: set `'views' => true` (we will register our own view names). Enable only: `Features::registration()`, `Features::resetPasswords()`, `Features::emailVerification()`. Add 2FA later if desired.
- In `bootstrap/providers.php`: ensure `App\Providers\FortifyServiceProvider::class` is registered.
- Do **not** copy any view files from vendor into `resources/views`. We will create our own.

**Step 4: Merge migrations**

- Fortify may publish migrations. If it publishes a migration that creates or alters `users` table, either remove it and keep the existing `0001_01_01_000000_create_users_table.php`, or merge any needed columns (e.g. nothing if our table already has name, email, password). Do not overwrite the existing users migration.

**Step 5: Commit**

```bash
git add composer.json composer.lock config/fortify.php app/Providers/FortifyServiceProvider.php app/Actions/Fortify bootstrap/providers.php
# Add only new migrations that don't conflict; omit or drop any that duplicate users table.
git commit -m "chore: add Laravel Fortify (auth backend only)"
```

---

## Task 2: Configure Fortify views and home path (no views published)

**Files:**
- Modify: `app/Providers/FortifyServiceProvider.php`

**Step 1: Register view callbacks**

In `FortifyServiceProvider::boot()`, register:

- `Fortify::loginView(fn () => view('auth.login'));`
- `Fortify::registerView(fn () => view('auth.register'));`
- `Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));`
- `Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));`
- `Fortify::verifyEmailView(fn () => view('auth.verify-email'));`

Do **not** publish vendor views. We will create `resources/views/auth/*.blade.php` in the next tasks.

**Step 2: Set home path**

In `config/fortify.php`, set `'home' => '/'` (or desired dashboard path).

**Step 3: Commit**

```bash
git add app/Providers/FortifyServiceProvider.php config/fortify.php
git commit -m "config: point Fortify to custom auth view names"
```

---

## Task 3: Add username (and optional profile/2FA) to User and migrations

**Files:**
- Create: `database/migrations/xxxx_add_username_to_users_table.php`
- Optional later: migrations for profile_photo_path, two_factor columns if 2FA is enabled.
- Modify: `app/Models/User.php` (add `username` to fillable/casts; ensure compatible with Fortify)

**Step 1: Migration for username**

```bash
php artisan make:migration add_username_to_users_table --no-interaction
```

In the migration: add `$table->string('username')->unique()->after('name');`. If Fortify’s config uses `'username' => 'email'`, we can still add a separate `username` for display; then in registration we collect both. Set `config('fortify.username')` to `'email'` for login (or `'username'` if we want login by username).

**Step 2: Update User model**

Add `username` to fillable (or guarded exception) and to casts. No Jetstream traits.

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

**Step 4: Commit**

```bash
git add database/migrations/xxxx_add_username_to_users_table.php app/Models/User.php
git commit -m "feat: add username to users"
```

---

## Task 4: Customize Fortify registration to handle username

**Files:**
- Modify: `app/Actions/Fortify/CreateNewUser.php` (add `username` from request; validate unique)
- Modify: `config/fortify.php` if we want login by username (optional)

**Step 1: Update CreateNewUser**

In `CreateNewUser`, accept `username` from the request, validate it (required, string, unique in users table), and pass it to `User::create([...])`.

**Step 2: Commit**

```bash
git add app/Actions/Fortify/CreateNewUser.php
git commit -m "feat: register username via Fortify CreateNewUser"
```

---

## Task 5: Create guest layout (optional) and auth views using existing app layout

**Files:**
- Create: `resources/views/layouts/guest.blade.php` (optional; can use `layouts.app` for consistency)
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`
- Create: `resources/views/auth/forgot-password.blade.php`
- Create: `resources/views/auth/reset-password.blade.php`
- Create: `resources/views/auth/verify-email.blade.php`

**Step 1: Use existing layout**

All auth views MUST extend `layouts.app` (or a minimal `layouts.guest` that does not change `vite`, `livewire`, or `flux` setup). Do not overwrite `resources/views/layouts/app.blade.php` with any vendor content.

**Step 2: Login view**

- Form: POST to `url('/login')`, method POST, CSRF, fields: `email` (or `username` per config), `password`, optional `remember`.
- Use Tailwind v4 and existing project class conventions. Show validation errors via `$errors`.

**Step 3: Register view**

- Form: POST to `url('/register')`, fields: `name`, `username`, `email`, `password`, `password_confirmation`. Same layout and error display.

**Step 4: Forgot password**

- Form: POST to `url('/forgot-password')`, field: `email`. Link to login.

**Step 5: Reset password**

- Form: POST to `url('/password/reset')`, with `token` and `email` from `$request`. Fields: `password`, `password_confirmation`. Named route `password.reset` must exist (Fortify registers it).

**Step 6: Verify email**

- Message + “Resend” link that POSTs to `url('/email/verification-notification')`. Follow Laravel email verification docs.

**Step 7: Commit**

```bash
git add resources/views/layouts/guest.blade.php resources/views/auth/
git commit -m "feat: add auth Blade views using app layout"
```

---

## Task 6: Wire auth links in app layout (login, register, logout)

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Add links**

In the header, add conditional links: when guest, show “Log in” (link to `/login`) and “Register” (link to `/register`). When authenticated, show user name/avatar (optional) and a form/link for “Log out” (POST to `/logout`).

**Step 2: Do not remove or break Flux appearance toggle**

Keep `flux:radio.group` and `@fluxScripts` / `@fluxAppearance` as in the current layout.

**Step 3: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: add auth links to app layout"
```

---

## Task 7: Optional – Login in dropdown (Livewire) instead of separate page

**Files:**
- Create: `app/Livewire/AuthDropdown.php` (or Volt under `resources/views/livewire/`)
- Create: `resources/views/livewire/auth-dropdown.blade.php`
- Modify: `resources/views/layouts/app.blade.php` (use Livewire component for “Log in” that opens dropdown with login form)

**Step 1: Livewire component**

Component shows “Log in” button; when clicked, toggle a dropdown that contains a form POSTing to `/login` (same fields as login view). Use Livewire 4 syntax. No Jetstream/Livewire 3 patterns.

**Step 2: Replace static login link**

In layout, when guest, render the Livewire auth dropdown instead of a plain link to `/login`.

**Step 3: Commit**

```bash
git add app/Livewire/AuthDropdown.php resources/views/livewire/auth-dropdown.blade.php resources/views/layouts/app.blade.php
git commit -m "feat: login form in header dropdown (Livewire)"
```

---

## Task 8: Email verification and password reset flow

**Files:**
- Modify: `app/Models/User.php` (ensure implements `MustVerifyEmail` if not already)
- Modify: `routes/web.php` (add `auth` middleware group with `verified` for routes that require verified email)
- Ensure `Illuminate\Auth\Middleware\EnsureEmailIsVerified` is used where needed

**Step 1: Verify User implements MustVerifyEmail**

Already in original User. No change if so.

**Step 2: Protect routes**

Any “dashboard” or authenticated-only routes should use `auth` and optionally `verified` middleware.

**Step 3: Commit**

```bash
git add app/Models/User.php routes/web.php
git commit -m "chore: ensure email verification used on protected routes"
```

---

## Task 9: Profile (avatar, update profile info) – optional

**Files:**
- Create: `database/migrations/xxxx_add_profile_photo_path_to_users_table.php` (if needed)
- Create: `app/Http/Controllers/ProfileController.php` or Livewire profile component
- Create: `resources/views/profile/edit.blade.php` (or Livewire view)
- Modify: `routes/web.php` (profile route)
- Fortify does not provide profile updates; implement manually (update name, username, avatar upload).

**Step 1: Migration for avatar**

Add `profile_photo_path` nullable string to users if desired.

**Step 2: Profile update logic**

Controller or Livewire: validate and update name, username; handle avatar upload (store in `public` or storage link). Use existing layout.

**Step 3: Commit**

```bash
git add database/migrations/ app/Http/Controllers/ProfileController.php resources/views/profile/ routes/web.php
git commit -m "feat: profile edit with avatar"
```

---

## Task 10: Two-factor authentication (optional)

**Files:**
- Create: migration for two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at on users
- Modify: `config/fortify.php` (enable `Features::twoFactorAuthentication()`)
- Implement 2FA views (enable, challenge, disable) that call Fortify’s 2FA routes; use Fortify actions for generating/confirming 2FA. No Jetstream; use Fortify’s 2FA API only.

**Step 1: Migration**

Add columns per Laravel Fortify 2FA documentation.

**Step 2: User model**

Use `Laravel\Fortify\TwoFactorAuthenticatable` trait (from Fortify, not Jetstream). Ensure trait is only from `laravel/fortify`.

**Step 3: Views and routes**

Fortify registers 2FA routes. Create views that POST to those routes; protect 2FA settings with `auth` and `password.confirm` if desired.

**Step 4: Commit**

```bash
git add config/fortify.php database/migrations/ app/Models/User.php resources/views/ routes/
git commit -m "feat: two-factor authentication (Fortify)"
```

---

## Task 11: Tests

**Files:**
- Create: `tests/Feature/Auth/RegistrationTest.php`
- Create: `tests/Feature/Auth/LoginTest.php`
- Create: `tests/Feature/Auth/PasswordResetTest.php`
- Create: `tests/Feature/Auth/EmailVerificationTest.php` (optional)
- Modify: `database/factories/UserFactory.php` (add `username` if present on User)

**Step 1: Registration test**

POST to `/register` with valid data; assert user exists and redirect. Test validation (duplicate email/username, invalid password).

**Step 2: Login test**

POST to `/login` with valid/invalid credentials; assert redirect and auth state.

**Step 3: Password reset test**

Request reset link; assert notification sent. Hit reset URL with token; submit new password; assert can log in.

**Step 4: Factory**

If User has `username`, add `username` to UserFactory (e.g. unique username).

**Step 5: Run tests**

```bash
php artisan test --compact tests/Feature/Auth/
```

**Step 6: Commit**

```bash
git add tests/Feature/Auth/ database/factories/UserFactory.php
git commit -m "test: auth feature tests"
```

---

## Summary

- **No Jetstream.** No Breeze. No starter-kit views or Livewire components from packages.
- **Fortify only:** config, provider, actions, routes. Our views and our layout.
- **Do not overwrite:** `resources/views/layouts/app.blade.php`, `resources/css/app.css`, `vite.config.js`, Tailwind/Livewire config with any vendor defaults.
- **Migrations:** Keep existing users table migration; add columns via new migrations. Review anything published by `fortify:install` and merge or drop to avoid conflicts.
- **2FA and profile/avatar:** Optional; implement with Fortify’s 2FA and custom profile logic.

Plan complete and saved to `docs/plans/2026-02-27-auth-mechanism-only.md`.
