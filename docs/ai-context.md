# AI Context Reference

Dense, search-friendly facts for AI agents working on this codebase. Read [README.md](./README.md) for the full doc map.

---

## Identity

- **Project:** Mazin Shoes ERP (`d:/Workspace/Projects/MazinShoes/website`)
- **Domain:** Wholesale shoe retail, Sudan, SDG/USD
- **Stack:** Laravel 13 + Inertia 3 + Vue 3 + Nuxt UI 4 + Tailwind 4 + Vite 8
- **NOT Livewire** (legacy README may be wrong)

---

## Critical paths

| Topic | Where to look |
|-------|---------------|
| Routes | `routes/web.php` |
| Sidebar menu | `config/navigation.php` |
| Permissions | `config/permissions.php`, `RolesAndPermissionsSeeder` |
| Inertia shared props | `app/Http/Middleware/HandleInertiaRequests.php` |
| Exchange rate | `app/Support/TenantMoney.php`, settings group `currency` key `exchange_rate` |
| Invoice PDF view | `app/Support/InvoiceDesign.php`, `config/invoice.php`, `resources/views/pdf/invoices/` |
| Sales logic | `app/Services/Sales/SalesService.php`, `SalesController` |
| Shop public | `ShopController`, `ResolveShopTenant` middleware, `ShopSettingsService` |
| Form drafts (JS) | `resources/js/composables/useFormDraft*.js`, `FormDraftReminder*.vue` |
| Calculator | `CalculatorFab.vue`, `CalculatorModal.vue`, `useCalculator.js` |
| Display prefs | `PreferenceController`, `useDisplayPreferences.js`, `Preferences/Display.vue` |
| Notifications poll | `NotificationSummaryController`, `useNotificationPoll.js` |
| Branch scope | `app/Services/Branch/BranchContext.php` |
| Tenant scope | `TenantScope` on models, `users.tenant_id` |

---

## Route name cheatsheet

```
dashboard, sales.*, products.*, purchases.*, inventory.*, transfers.*,
customers.*, suppliers.*, debts.*, expenses.*, shipments.*, logistics.*,
reports.*, analytics.*, branches.*, users.*, settings.*, shop.settings,
shop.index, shop.show, notifications.*, data-tools.*, platform.*, links.index
```

---

## Inertia pages pattern

`resources/js/pages/{Name}/Index.vue` — props from matching `*Controller@index`.

Layouts: `AppLayout`, `GuestLayout`, `ShopLayout`.

---

## Common composables

`useTrans`, `useDirection`, `usePermissions`, `useMoney`, `useResourceForm`, `useFormDraft`, `useFormDraftRegistry`, `useFormDraftModal`, `useCalculator`, `useCalculatorModal`, `useDisplayPreferences`, `useFloatingFabStack`, `useNotificationPoll`, `useNotificationSound`, `useTableFilters`, `useTableColumns`, `useIndexTable`

---

## Multi-tenancy rules

1. Catalog models: filtered by `tenant_id` global scope.
2. Operational data: filtered by `BranchContext` active branch.
3. Settings: tenant-wide default, optional branch override via `SettingsManager`.
4. Public shop: resolved by `tenants.slug` in URL — **404 if shop disabled** (except admin preview).

---

## Exchange rate (important bug history)

- **Wrong:** `config('money.default_exchange_rate')` (600 env default)
- **Right:** `TenantMoney::exchangeRate()` from Settings → Currency
- **Frontend:** `page.props.money.exchangeRate` (global Inertia prop)
- **Do not** re-add per-page `defaultExchangeRate` from config

---

## Form drafts

- Storage: `localStorage` key `ms:drafts:{userId}:{tenantId}`
- Restore: navigate with `?draft={key}` e.g. `sales.create`, `settings.currency`
- Cleared on successful form submit
- Modal must use Nuxt UI `#body` slot

---

## Floating FAB stack (end side)

Bottom offsets via `useFloatingFabStack.js`:

| Index | FAB | Class |
|-------|-----|-------|
| 0 | Calculator (always) | `bottom-6` |
| 1 | Form draft reminder (when count > 0) | `bottom-20` |
| 2 | Tablet launcher (tablet mode) | `bottom-[8.5rem]` |

---

## Display preferences

Stored in `users.settings.display`:

```json
{
  "scale": "sm|md|lg|xl",
  "text_body": "#111827|null",
  "text_muted": "#6b7280|null",
  "high_contrast": false
}
```

- Routes: `preferences.display` (GET), `preferences.display.save` (POST)
- Shared prop: `displayPrefs` in HandleInertiaRequests
- Applied via `data-ui-scale`, `data-custom-text`, `data-high-contrast` on `<html>`
- CSS: `resources/css/inertia.css` — scale steps + `.responsive-stat-grid`

---

## Invoice designs

Keys: `classic`, `minimal`, `compact` in `config/invoice.php`  
Setting: `SettingsManager` group `invoice` key `design`  
Preview: `GET settings/invoice/preview/{design}`

---

## Shop settings

- Admin: `/settings/shop` (`ShopSettingsController`)
- Public: `/shop/{slug}` — requires `shop.enabled` setting + hero title on save
- Product flags: `show_in_shop`, `featured_in_shop`, `shop_description`

---

## Tests

Run: `php artisan test` (142+ tests)  
Feature tests in `tests/Feature/{Domain}/`  
Seed pattern: `RolesAndPermissionsSeeder`, `ModuleSeeder`, create tenant+branch+admin

---

## i18n

Files: `lang/ar/*.php`, `lang/en/*.php`  
Client: all groups loaded to `translations` prop  
Usage: `t('group.key')` — add keys to **both** locales

---

## Docs map

| File | Content |
|------|---------|
| `docs/README.md` | Index |
| `docs/features.md` | Feature list |
| `docs/modules-reference.md` | Routes & permissions |
| `docs/changelog.md` | History |
| `docs/roadmap.md` | Future work |
| `docs/user-guide.md` | End-user how-to |
| `docs/architecture.md` | Tech architecture |

---

## When editing code

1. Match existing patterns (`useResourceForm`, service layer, permission middleware).
2. Update `docs/changelog.md` for user-visible changes.
3. Add/update tests in `tests/Feature/`.
4. Run `php artisan test` and `npm run build`.
5. Never commit `.env` secrets.

---

*Optimized for grep and AI retrieval. Last updated July 2026.*
