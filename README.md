# Mazin Shoes ERP

A multi-branch retail ERP for Mazin Shoes (Sudan) — inventory, sales, purchasing,
debts, expenses, shipping, and financial reporting — built as a Laravel full-stack
application with a bilingual (Arabic RTL / English LTR) interface.

## Tech stack

| Concern        | Choice                                             |
| -------------- | -------------------------------------------------- |
| Framework      | Laravel 13 (PHP 8.3+)                              |
| Interactivity  | Livewire 4 (single-file components) + Alpine       |
| UI kit         | Mary UI + Tailwind CSS v4 + daisyUI (`mazin` theme) |
| Database       | MySQL 8 in production · SQLite for local/test       |
| AuthZ          | spatie/laravel-permission                          |
| Audit          | spatie/laravel-activitylog                         |
| Media          | spatie/laravel-medialibrary                        |
| PDF / Images   | barryvdh/laravel-dompdf · intervention/image       |
| Fonts          | Roboto (Latin) + Tajawal (Arabic), self-hosted     |

## Local setup

```bash
composer install
npm install
cp .env.example .env          # then set APP_NAME, APP_LOCALE=ar, etc.
php artisan key:generate
php artisan migrate --seed     # creates the Mazin Shoes tenant + admin
npm run build                  # or: npm run dev
php artisan serve
```

Default admin: `admin@mazinshoes.com` / `password`.

### Required PHP extensions

`pdo_sqlite`, `sqlite3` (local), `zip`, `gd`, `exif`, `fileinfo`, `intl`, `bcmath`.
On Linux production also enable `pcntl` (needed by scheduled backups in Phase 11).

## Architecture

Layered: **Livewire components → Form objects / Actions → Services → Eloquent**.

### Branch isolation (the security backbone)

- `App\Services\Branch\BranchContext` resolves the active branch and the set of
  branch IDs the current user may access (the consolidated "All branches" view is
  available to users with `branches.view_all`).
- `App\Models\Concerns\BelongsToBranch` adds the `App\Models\Scopes\BranchScope`
  global scope and auto-fills `branch_id` on create. Every tenant-owned, branch-level
  table uses this trait, guaranteeing reads/writes never cross branch boundaries.
- Escape hatches for trusted server code: `Model::query()->allBranches()` and
  `->forBranch($id)`.

### Tenancy-ready, single-tenant today

The schema carries `tenant_id` throughout so the app can become a white-label SaaS
(Phase 13) without a migration rewrite, while Mazin Shoes runs as a single tenant.

### Roles & permissions

Tenant-wide roles (`admin`, `branch_manager`, `accountant`, `salesperson`,
`inventory_clerk`, `viewer`) defined in `config/permissions.php`. `super_admin`
bypasses all checks via a `Gate::before` hook. Per-branch *capability* differences
are deferred to Phase 13 (the `branch_id` team key is reserved on the permission
pivots); branch **data** isolation is already fully enforced by `BranchScope`.

### Other core services

- `SettingsManager` — tenant/branch-scoped settings with branch-over-tenant resolution.
- `ModuleManager` — per-tenant module enable/disable, drives the dynamic menu.
- `DocumentNumberService` — gap-free, per-branch document numbering under row locks.
- `Navigation` — permission- and module-aware sidebar menu (`config/navigation.php`).
- `GlobalSearch` — provider registry for branch-scoped global search.

## Quality gates

```bash
./vendor/bin/pint           # code style
./vendor/bin/phpstan analyse # static analysis (level 5 + Larastan)
php artisan test            # PHPUnit
```

CI runs all three plus an asset build on every push/PR (`.github/workflows/ci.yml`).
