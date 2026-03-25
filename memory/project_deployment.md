---
name: Game Discovery — production deployment
description: How the app is deployed and how to run commands in production
type: project
---

Deployed via Docker Compose. Three containers: app, scheduler, queue.

**Container access:**
```bash
docker exec -it game-discovery-app php artisan tinker
docker exec -it game-discovery-app php artisan migrate --force
```

**After deploy:**
```bash
bun run build  # rebuild assets locally before pushing
docker exec -it game-discovery-app php artisan config:cache
docker exec -it game-discovery-app php artisan view:cache
```

**Create admin user via tinker:**
```php
User::where('email', 'email@example.com')->update(['role' => 'admin']);
// Admin panel at /admin
```

**Why:** User deploys from local, pushes to prod via git, Docker handles the rest.

**How to apply:** Always suggest `bun run build` before prod deploy when frontend files change. Use `docker exec -it game-discovery-app` prefix for all prod artisan commands.
