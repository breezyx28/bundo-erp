# Mazin Shoes ERP — cPanel Deployment Runbook & Rollback

This runbook covers deploying the Laravel 13 + Livewire 4 application to a
shared/managed **cPanel** host, plus a tested rollback procedure. It assumes
the host provides: PHP 8.3+, MySQL 8.0+, SSH (or Terminal in cPanel), Composer,
and a cron scheduler. Redis is optional (recommended in production).

---

## 0. Server prerequisites (one time)

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.3 | Enable extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `gd`, `zip`, `intl` |
| MySQL | 8.0 | Create DB + user with `ALL PRIVILEGES` on that DB |
| Composer | 2.x | `composer --version` |
| Node | 20+ | Only needed if building assets **on** the server (prefer building locally/CI) |
| Redis | 7.x | Optional: cache + queue + session driver |

Confirm the PHP CLI version matches the web PHP version in cPanel
(**MultiPHP Manager**). Set the domain's document root to the project's
`public/` directory (see §3).

---

## 1. Build artifacts (locally or in CI — preferred)

Building on the server is slow and memory-constrained on shared hosting. Build
the front-end **before** uploading:

```bash
# On a developer machine / CI runner
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build            # outputs public/build (Vite manifest + hashed assets)
```

Commit/ship the resulting `public/build/` directory with the release. Never run
`npm run dev` in production.

---

## 2. First-time release layout (zero-downtime friendly)

Use a releases + symlink layout so rollback is instant:

```
~/mazinshoes/
├── shared/                 # persists across releases
│   ├── .env                # production secrets (NEVER in git)
│   └── storage/            # the real storage dir (uploads, logs, sessions)
├── releases/
│   ├── 2026-06-26-120000/  # one folder per deploy (timestamped)
│   └── 2026-06-26-150000/
└── current -> releases/2026-06-26-150000   # symlink the live release
```

The cPanel domain's **Document Root** must point to:
`~/mazinshoes/current/public`.

> If your host does not allow a document root outside `public_html`, point the
> domain at `public_html/` and make `public_html` a symlink to
> `~/mazinshoes/current/public`, or deploy the repo into `public_html` and set
> the addon-domain root to `public_html/public`.

---

## 3. Deploy a new release

Run from `~/mazinshoes`:

```bash
set -euo pipefail

REL="releases/$(date +%Y-%m-%d-%H%M%S)"

# 1) Get the code (git or uploaded tarball that already includes vendor + build)
git clone --depth 1 --branch main <REPO_URL> "$REL"
cd "$REL"

# 2) Link shared, persistent state
ln -nfs ~/mazinshoes/shared/.env       .env
rm -rf storage && ln -nfs ~/mazinshoes/shared/storage storage

# 3) Install PHP deps (skip if vendor/ was shipped in the artifact)
composer install --no-dev --optimize-autoloader --no-interaction

# 4) Migrate (forced, non-interactive) — see §5 for the safe procedure
php artisan migrate --force

# 5) Cache config/routes/views/events for production speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link        # public/storage -> storage/app/public

# 6) Flip the symlink (this is the atomic "go live" step)
ln -nfs ~/mazinshoes/"$REL" ~/mazinshoes/current

# 7) Reload queue workers so they pick up new code
php artisan queue:restart
```

> **Order matters:** run migrations and warm caches *before* flipping `current`.
> The switch in step 6 is the only user-visible cutover.

---

## 4. Production `.env` checklist

Edit `~/mazinshoes/shared/.env`:

```dotenv
APP_NAME="Mazin Shoes"
APP_ENV=production
APP_DEBUG=false                 # MUST be false in production
APP_URL=https://erp.example.com
APP_KEY=base64:...              # php artisan key:generate --show (set once, keep stable)

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=mazin_erp
DB_USERNAME=mazin_user
DB_PASSWORD=********

# Prefer redis in production; fall back to database/file on basic shared hosting.
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true      # HTTPS only — hardens the session cookie
SESSION_SAME_SITE=lax

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (notifications / reminders)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="erp@example.com"

# Backups (spatie/laravel-backup) — destination is local disk by default
BACKUP_ARCHIVE_PASSWORD=********
```

After editing env, always re-run `php artisan config:cache`.

Security defaults already enforced by the app:
- `App\Http\Middleware\SecurityHeaders` adds CSP, X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and HSTS (HTTPS).
- Login is rate-limited (5 failed attempts per email+IP, plus route throttle).
- All transactional data is branch-isolated via `BranchScope`
  (see `tests/Feature/Security/BranchIsolationTest.php`).

---

## 5. Database migration safety

1. **Back up first** (see §7). Never migrate without a fresh dump.
2. Review pending migrations: `php artisan migrate:status`.
3. Apply: `php artisan migrate --force`.
4. The Phase 12 hardening migration (`*_harden_indexes_and_constraints.php`)
   changes the invoice / PO / transfer **unique keys** to be branch-scoped. If a
   legacy single-branch dataset somehow has duplicate numbers across branches,
   resolve duplicates before migrating (the unique index creation will fail
   otherwise).

---

## 6. Cron: scheduler + queue worker

cPanel → **Cron Jobs**. Add the Laravel scheduler (every minute):

```
* * * * * cd ~/mazinshoes/current && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

The scheduler already drives (see `routes/console.php`):
- `notifications:scan` — low-stock + overdue-debt alerts
- `backup:run` — daily/weekly database + file backups
- `backup:clean` — prune old archives

For the queue, prefer a long-running worker via cron keep-alive (shared hosts
usually lack supervisor):

```
* * * * * cd ~/mazinshoes/current && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 >> storage/logs/queue.log 2>&1
```

---

## 7. Backups (before every deploy)

On-demand database dump (independent of the in-app backup feature):

```bash
mysqldump -u mazin_user -p mazin_erp > ~/mazinshoes/backups/db-$(date +%F-%H%M%S).sql
```

The app also exposes managed backups via `spatie/laravel-backup`
(`php artisan backup:run`) and the in-app **Data Tools → Backups** screen.
Keep at least the last 7 daily + 4 weekly archives off-server.

---

## 8. Smoke test after cutover

```bash
curl -I https://erp.example.com/up           # health check -> 200
curl -I https://erp.example.com/login         # -> 200, security headers present
```

Then manually verify: login, dashboard KPIs load, create a test cash sale,
open an invoice PDF, switch branch, switch language (AR/EN), and confirm the
PWA install prompt + offline page (`/offline.html`).

---

## 9. Rollback (instant)

Because `current` is a symlink, rollback is a single re-point. **Code rollback
is instant; database rollback is the risky part — handle it explicitly.**

### 9a. Code-only rollback (no schema change in the bad release)

```bash
cd ~/mazinshoes
ls -1dt releases/*/ | head    # find the previous good release dir
ln -nfs ~/mazinshoes/releases/<PREVIOUS_GOOD> current
cd current
php artisan config:cache route:cache view:cache
php artisan queue:restart
```

### 9b. Rollback when the bad release ran migrations

1. Re-point `current` to the previous release (as in 9a).
2. Reverse the schema **only if** the previous code is incompatible with the
   new schema:
   ```bash
   php artisan migrate:rollback --step=1 --force
   ```
   Prefer this over a full DB restore. If the migration was destructive or
   `migrate:rollback` is unsafe, restore the pre-deploy dump from §7:
   ```bash
   mysql -u mazin_user -p mazin_erp < ~/mazinshoes/backups/db-<TIMESTAMP>.sql
   ```
3. `php artisan config:cache && php artisan queue:restart`.
4. Re-run the §8 smoke test.

### 9c. Emergency maintenance mode

If you need to take the app down while fixing:

```bash
php artisan down --secret="<long-random-token>"   # bypass via ?secret=<token>
# ...fix...
php artisan up
```

---

## 10. Release retention

Keep the last ~5 releases for fast rollback, prune older ones:

```bash
cd ~/mazinshoes/releases && ls -1dt */ | tail -n +6 | xargs rm -rf
```

---

## 11. Brand assets still required

The PWA manifest (`public/manifest.webmanifest`) references brand icons at
`public/icons/icon-192.png` and `public/icons/icon-512.png`. Drop the final
brand PNGs there before launch so the install prompt shows the correct logo.
A `favicon.ico` is already linked.
