# Game Discovery — Plan d'améliorations

## Vue d'ensemble

| # | Proposition | Complexité | Statut |
|---|-------------|------------|--------|
| 1 | Statut de jeu dans le backlog (tracked_games.status) | S | À faire |
| 2 | Formulaire de soumission de game request | S | À faire |
| 3 | Upload de photo de profil | S | À faire |
| 4 | Retry logic sur SyncGameJob | S | À faire |
| 5 | Soft deletes sur Game | M | À faire |
| 6 | Notifications de sorties | M | À faire |
| 7 | Filtre par genre sur /games | S | À faire |
| 8 | Indicateur "nouveau" dans le feed dashboard | M | À faire |
| 9 | Page 404 custom | S | À faire |
| 10 | Logs d'enrichissement persistants | M | À faire |

---

## 1 — Statut de jeu dans le backlog

**Complexité : S**

### Fichiers à créer
- `database/migrations/YYYY_MM_DD_add_status_to_tracked_games_table.php`
- `app/Enums/TrackedGameStatus.php`

### Fichiers à modifier
- `app/Models/TrackedGame.php`
- `app/Models/User.php` (ajouter `withPivot('status')`)
- `app/Models/Game.php` (ajouter `withPivot('status')`)
- `resources/views/components/⚡dashboard-game-list.blade.php`

### Étapes

1. **Créer l'enum** `app/Enums/TrackedGameStatus.php` :
   ```php
   enum TrackedGameStatus: string {
       case ToPlay    = 'to_play';
       case Playing   = 'playing';
       case Completed = 'completed';
       case Dropped   = 'dropped';

       public function label(): string { ... }
   }
   ```

2. **Migration** — colonne nullable sans valeur par défaut (null = non défini) :
   ```php
   $table->string('status')->nullable()->default(null)->after('game_id');
   ```

3. **Mettre à jour `TrackedGame`** — ajouter `'status'` au `$fillable` et `'status' => TrackedGameStatus::class` dans `casts()`.

4. **Ajouter `->withPivot('status')`** sur `User::trackedGames()` et `Game::trackedByUsers()`.

5. **Ajouter l'action Livewire** dans `⚡dashboard-game-list.blade.php` :
   ```php
   public function updateStatus(int $gameId, string $statusValue): void
   {
       $user = auth()->user();
       $user->trackedGames()->updateExistingPivot($gameId, [
           'status' => $statusValue !== '' ? $statusValue : null,
       ]);
   }
   ```

6. **Ajouter le `<select>`** dans la boucle de jeux du dashboard avec `wire:change="updateStatus({{ $game->id }}, $event.target.value)"`.

7. **Ajouter un filtre par statut** dans les contrôles existants (`public string $statusFilter = ''`, `->wherePivot('status', $this->statusFilter)`).

### Décisions / Avertissements
- Utiliser `null` (pas `to_play` comme défaut) — les jeux sans statut restent neutres.
- Utiliser `updateExistingPivot()`, pas `syncWithoutDetaching()`.
- PHPStan : ajouter `@property-read` pour le pivot si nécessaire.

---

## 2 — Formulaire de soumission de game request

**Complexité : S**

### Fichiers à créer
- `app/Http/Controllers/GameRequestController.php`
- `resources/views/pages/game-requests.blade.php`

### Fichiers à modifier
- `routes/web.php`
- `resources/views/layouts/app.blade.php` (optionnel — lien nav)

### Étapes

1. **Route publique** dans `routes/web.php` :
   ```php
   Route::get('/request-game', [GameRequestController::class, 'index'])->name('game-requests.index');
   ```

2. **Controller** — charge les 20 requêtes les plus votées :
   ```php
   public function index(): View
   {
       $topRequests = GameRequest::query()
           ->pending()
           ->orderByDesc('request_count')
           ->limit(20)
           ->get();
       return view('pages.game-requests', compact('topRequests'));
   }
   ```

3. **Vue Blade** — layout app, intègre `<livewire:game-request-card />` (déjà fonctionnel) + liste des top requests avec badge DaisyUI.

4. **Lien "Vous ne trouvez pas un jeu ?"** sur `/games` pointant vers `route('game-requests.index')`.

### Décisions / Avertissements
- La logique de déduplication et de vote est déjà dans le composant `⚡game-request-card`. Ne pas dupliquer.
- Route sans auth — le composant gère lui-même l'état non-authentifié.

---

## 3 — Upload de photo de profil

**Complexité : S**

La colonne `profile_photo_path` et le `ProfileController` existent déjà.

### Fichiers à modifier
- `app/Http/Requests/UpdateProfileRequest.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/layouts/app.blade.php`

### Étapes

1. **Ajouter la règle de validation** dans `UpdateProfileRequest::rules()` :
   ```php
   'photo' => ['nullable', 'image', 'max:2048'],
   ```

2. **Vérifier `storage:link`** dans la procédure de déploiement (déjà géré par Artisan, à documenter).

3. **Harmoniser le style** de `profile/edit.blade.php` avec DaisyUI 5 (inputs `input input-bordered`, bouton `btn btn-primary`, sections en `card`).

4. **Afficher l'avatar dans la nav** :
   ```blade
   @if (auth()->user()->profile_photo_path)
       <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
            class="size-6 rounded-full object-cover" alt="" />
   @else
       {{ Str::substr(auth()->user()->name, 0, 1) }}
   @endif
   ```

5. **Option "supprimer la photo"** — checkbox dans le form, vérifiée dans le controller.

### Décisions / Avertissements
- Chemin relatif au disk `public` : `asset('storage/'.$path)` est correct.
- PHPStan : `$request->file('photo')` retourne `UploadedFile|array|null` — le guard `hasFile()` déjà présent dans le controller suffit.

---

## 4 — Retry logic sur SyncGameJob

**Complexité : S**

### Fichiers à modifier
- `app/Jobs/SyncGameJob.php`
- `app/Services/IgdbGameDataProvider.php`
- `app/Services/RawgGameDataProvider.php`

### Étapes

1. **Ajouter les propriétés de retry** sur `SyncGameJob` :
   ```php
   public int $tries = 3;

   /** @return array<int, int> */
   public function backoff(): array { return [30, 120]; }
   ```

2. **Ajouter la méthode `failed()`** :
   ```php
   public function failed(Throwable $exception): void
   {
       Log::error('SyncGameJob permanently failed', [
           'externalId'     => $this->externalId,
           'externalSource' => $this->externalSource,
           'gameId'         => $this->gameId,
           'error'          => $exception->getMessage(),
       ]);
   }
   ```

3. **Arrêter les retries sur erreur de config** (credentials manquants = non-transitoire) :
   ```php
   try {
       $details = $provider->getGameDetails($this->externalId);
   } catch (InvalidArgumentException $e) {
       $this->fail($e);
       return;
   }
   ```

### Décisions / Avertissements
- Ne pas ajouter `$tries` à `EnrichNewsJob` — son comportement sur retry créerait des doublons de News.
- `backoff()` doit retourner `array<int, int>` — ajouter le docblock PHPDoc.
- Vérifier que `QUEUE_CONNECTION` n'est pas `sync` en production.

---

## 5 — Soft deletes sur Game

**Complexité : M**

### Fichiers à créer
- `database/migrations/YYYY_MM_DD_add_soft_deletes_to_games_table.php`

### Fichiers à modifier
- `app/Models/Game.php`
- `app/Models/User.php` (relation `trackedGames`)
- `app/Models/Game.php` (relation `trackedByUsers`)
- `app/Services/DashboardFeedService.php`
- `app/Jobs/SyncGameJob.php`
- `app/Http/Controllers/Admin/GameController.php`

### Étapes

1. **Migration** :
   ```php
   $table->softDeletes();
   ```

2. **Ajouter `SoftDeletes`** sur le modèle `Game`.

3. **Cascades** — option A (recommandée) :
   - Laisser `news` et `game_activities` en place après soft-delete (les queries les filtrent via `game_id IN (tracked_ids)`).
   - Ajouter `->nullOnDelete()` sur `game_requests.game_id` (déjà nullable).
   - Ajouter `->cascadeOnDelete()` sur `news.game_id` et `game_activities.game_id` pour les force-delete.

4. **`User::trackedGames()`** — ajouter `->whereNull('games.deleted_at')` (les `BelongsToMany` n'appliquent pas automatiquement le scope `SoftDeletes`) :
   ```php
   return $this->belongsToMany(Game::class, 'tracked_games')
       ->withTimestamps()
       ->withPivot('status')
       ->whereNull('games.deleted_at');
   ```

5. **`SyncGameJob`** — ajouter `->withTrashed()` pour resynchroniser un jeu soft-deleted :
   ```php
   $existing = Game::withTrashed()
       ->where('external_source', $details['external_source'])
       ->where('external_id', $details['external_id'])
       ->first();
   // ... puis $game->restore() si $game->trashed()
   ```

6. **Admin** — filtre `?trashed=1` dans `GameController` pour afficher les jeux supprimés aux admins.

### Décisions / Avertissements
- Le route model binding via `slug` retourne automatiquement 404 pour les jeux soft-deleted (scope global).
- Explicitement ajouter `->whereNull('games.deleted_at')` sur les BelongsToMany — ne pas supposer que le scope s'applique automatiquement.

---

## 6 — Notifications de sorties

**Complexité : M**

### Fichiers à créer
- `app/Notifications/GameReleasedNotification.php`
- `app/Notifications/ReleaseDateChangedNotification.php`
- Migration `notifications` table (via `php artisan notifications:table`)

### Fichiers à modifier
- `app/Services/GameActivityRecorder.php`

### Étapes

1. **Créer la table notifications** : `php artisan notifications:table`.

2. **Créer les notifications** (implémentent `ShouldQueue`, channels `['database', 'mail']`).

   `ReleaseDateChangedNotification` reçoit `$game`, `$oldDate`, `$newDate`.
   `GameReleasedNotification` reçoit `$game`.

3. **Ajouter `notifyTrackers()` dans `GameActivityRecorder`** :
   ```php
   private function notifyTrackers(Game $game, Notification $notification): void
   {
       $game->trackedByUsers()->each(fn (User $u) => $u->notify($notification));
   }
   ```

4. **Appeler après chaque activité** dans `recordReleaseChanges()` :
   - Après `ReleaseDateChanged` → `$this->notifyTrackers($game, new ReleaseDateChangedNotification(...))`.
   - Après `GameReleased` → `$this->notifyTrackers($game, new GameReleasedNotification($game))`.
   - Ne pas notifier `ReleaseDateAnnounced` (trop de bruit sur les nouvelles entrées).

5. **Gate de préférence** (optionnel) — colonne `notify_release_changes boolean default true` sur `users`, vérifiée dans `notifyTrackers()`.

### Décisions / Avertissements
- Les notifications doivent implémenter `ShouldQueue` — elles sont déclenchées depuis `SyncGameJob` (déjà en queue).
- `toDatabase()` doit retourner un tableau plat (pas d'objets).
- Pour des volumes élevés de trackers, remplacer `->each()` par `->chunkById()`.

---

## 7 — Filtre par genre sur /games

**Complexité : S**

### Fichiers à modifier
- `resources/views/pages/⚡games.blade.php`

### Étapes

1. **Ajouter la propriété URL-bound** :
   ```php
   #[Url]
   public string $genre = '';

   public function updatedGenre(): void { $this->resetPage(); }
   ```

2. **Computed property pour les genres disponibles** :
   ```php
   /** @return array<int, string> */
   public function getAvailableGenresProperty(): array
   {
       return Game::query()
           ->whereNotNull('genres')
           ->pluck('genres')
           ->flatMap(fn (array $g) => $g)
           ->unique()->sort()->values()->all();
   }
   ```

3. **Appliquer le filtre** dans la query des jeux :
   ```php
   ->when($this->genre !== '', fn ($q) => $q->whereJsonContains('genres', $this->genre))
   ```

4. **Ajouter le `<select>` DaisyUI** dans le template, avec `wire:model.live="genre"`.

### Décisions / Avertissements
- `whereJsonContains` fonctionne avec SQLite (extension JSON1 activée par défaut).
- Envisager `Cache::remember('available_genres', 3600, ...)` si la table games devient grande.
- `#[Url]` rend le filtre persistable et partageable via URL.

---

## 8 — Indicateur "nouveau" dans le feed dashboard

**Complexité : M**

### Fichiers à créer
- `database/migrations/YYYY_MM_DD_add_last_feed_read_at_to_users_table.php`

### Fichiers à modifier
- `app/Models/User.php`
- `app/Services/DashboardFeedService.php`
- `resources/views/components/⚡dashboard-feed.blade.php`

### Étapes

1. **Migration** — ajouter `last_feed_read_at timestamp nullable` sur `users`.

2. **`DashboardFeedService`** — ajouter `is_new: bool` à chaque item :
   ```php
   'is_new' => $user->last_feed_read_at === null
       || $item['occurred_at']->isAfter($user->last_feed_read_at),
   ```
   Mettre à jour le `@return` docblock avec le nouveau champ.

3. **`mount()` dans `⚡dashboard-feed`** — marquer comme lu à chaque chargement :
   ```php
   public function mount(): void
   {
       auth()->user()?->forceFill(['last_feed_read_at' => now()])->save();
   }
   ```

4. **Badge DaisyUI** dans le template pour chaque item `is_new` :
   ```blade
   @if ($item['is_new'])
       <span class="badge badge-primary badge-xs">New</span>
   @endif
   ```

### Décisions / Avertissements
- `mount()` avance `last_feed_read_at` à chaque visite → les items actuels apparaissent "nouveaux" sur cette visite, pas la suivante. C'est le comportement attendu.
- Pas de marquage par item (pas de requête réseau par clic) — un seul timestamp suffit.

---

## 9 — Page 404 custom

**Complexité : S**

### Fichiers à créer
- `resources/views/errors/404.blade.php`
- `resources/views/errors/500.blade.php` (optionnel)
- `resources/views/errors/419.blade.php` (optionnel — CSRF expired)

### Étapes

1. **Créer `resources/views/errors/404.blade.php`** avec le layout app, un titre "404", message d'erreur et liens "Retour à l'accueil" et "Parcourir les jeux".

2. **Test Pest** :
   ```php
   it('shows a branded 404 page', function (): void {
       $this->get('/this-route-does-not-exist')
            ->assertStatus(404)
            ->assertSee('Page not found');
   });
   ```

### Décisions / Avertissements
- Laravel auto-discover `resources/views/errors/{status}.blade.php`.
- En dev avec `APP_DEBUG=true`, Ignition intercepte les 404. Tester avec `APP_DEBUG=false` ou via le test Pest.

---

## 10 — Logs d'enrichissement persistants

**Complexité : M**

### Fichiers à créer
- `database/migrations/YYYY_MM_DD_create_enrichment_runs_table.php`
- `app/Models/EnrichmentRun.php`
- `resources/views/components/⚡admin-enrichment-history.blade.php`

### Fichiers à modifier
- `app/Services/NewsEnrichmentService.php`
- `resources/views/admin/news-enrichment.blade.php`

### Étapes

1. **Migration** — table `enrichment_runs` :
   ```
   id, run_id (unique), status (running/completed/failed),
   feeds_total, feeds_done, created_count, error (nullable),
   started_at, finished_at (nullable), timestamps
   ```

2. **Modèle `EnrichmentRun`** — `final`, PHPDoc complet, `$fillable`, `casts()` pour les timestamps.

3. **Mettre à jour `NewsEnrichmentService::enrich()`** :
   - Créer la ligne DB au début du run (`status = 'running'`).
   - Mettre à jour à la fin (`status = 'completed'`).
   - Mettre à jour en cas d'exception (`status = 'failed'`, stocker `$e->getMessage()`).
   - Conserver les appels `Cache::put()` existants (le composant live-progress en dépend).

4. **Composant `⚡admin-enrichment-history`** — tableau DaisyUI des 20 derniers runs : run_id, statut (badge coloré), articles créés, feeds, durée, date.

5. **Intégrer** dans `resources/views/admin/news-enrichment.blade.php` sous le composant de progression existant.

### Décisions / Avertissements
- `run_id` est le même UUID que la clé Cache → corrélation facile entre run live et historique.
- `NewsEnrichmentService` est `final readonly class` — pas de mutation de propriétés, les updates DB utilisent le query builder statique.
- Prévoir une commande de nettoyage planifiée pour purger les runs > 90 jours.
- Pour un refresh automatique de l'historique en fin de run : le composant principal peut `$this->dispatch('enrichment-completed')` et l'historique l'écouter avec `#[On('enrichment-completed')]`.
