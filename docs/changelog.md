# Changelog

History of notable application changes. Newest first.

Format: **Date** — **Area** — Summary

---

## July 2026

### 2026-07-05 — Calculator & display preferences
- Floating calculator FAB (always visible) with standard, tax, discount, margin, and SDG↔USD modes.
- Per-user display settings at `/preferences/display`: UI scale (4 steps), custom text colors, high-contrast preset.
- Responsive stat grids adapt column count at larger scales to avoid horizontal scroll.
- Profile menu link to display preferences; prefs applied on boot and Inertia navigation.

### 2026-07-05 — Shop settings UX
- Added form validation display on `/settings/shop` (hero title when enabled, email, banner URLs).
- Centered save button; status alerts for live vs preview-only shop.
- Admins can preview disabled shop while logged in; public visitors still require enabled shop.
- Fixed boolean normalization on multipart shop save.

### 2026-07-05 — Notifications & UI polish
- Lightweight JSON polling (`GET /notifications/summary`) every 30s (visibility-aware).
- Notification sound (`/sounds/notification.wav`) with user preference toggle.
- Invoice settings: static cover thumbnails + preview modal (replaced inline iframes).
- Form draft modal fix (`#body` slot); single shared modal instance.
- Floating FAB always on `end-6` (opposite sidebar in LTR and RTL).

### 2026-07-05 — Form drafts, invoice designs, exchange rate
- **Form drafts:** localStorage auto-save across modals and settings forms; reminder badge (topbar mobile/tablet, floating desktop); restore via `?draft=`.
- **Invoice designs:** Classic, Minimal, Compact templates; Settings picker + preview route; tenant default in settings.
- **Exchange rate fix:** `TenantMoney::exchangeRate()` from tenant settings; shared as `money.exchangeRate` in Inertia; removed hardcoded 600 fallbacks in services/controllers.
- Tests: `InvoiceDesignTest`, `ExchangeRateTest` (+142 total tests).

### 2026-07-05 — Sales drafts & public shop (earlier)
- Server-side sales hold/draft (`sales.draft`, post, discard).
- Public marketing shop: `/shop/{slug}`, product visibility flags, shop settings page.
- Migration: sales draft fields + shop product fields.

### 2026-07-04 — Tablet mode & navigation
- Per-user layout mode (`regular` | `tablet`).
- Links launcher page (`/links`) with app-icon grid.
- Nav badges on sidebar items.
- Command palette + suggestions endpoint.

---

## June 2026 (stack migration & core ERP)

### 2026-06 — Frontend stack migration
- **Major:** Replaced Livewire/Mary UI with **Inertia + Vue 3 + Nuxt UI**.
- Vite 8 build pipeline; Ziggy for named routes in Vue.
- Shared translations loaded to client for `useTrans()`.

### 2026-06 — Core ERP phases (2–13)
- Multi-tenant, multi-branch architecture.
- Modules: sales, purchasing, inventory, debts, expenses, shipping, reports, analytics.
- Spatie permissions; role seeder.
- Dashboard KPIs with caching.
- Data tools: Excel import/export, backups.
- Platform admin: tenant CRUD, enter/exit tenant.
- Onboarding wizard for new tenants.
- PWA manifest + service worker shell.
- Bilingual UI (ar/en) with RTL.

---

## Infrastructure & ops

- SQLite local / MySQL production.
- Encrypted production env support (`.env.encrypted` commits in git history).
- CI: npm lockfile sync fixes for `npm ci`.
- Larastan static analysis configured.

---

## How to update this file

When shipping a user-visible or architectural change:

```markdown
### YYYY-MM-DD — Short title
- Bullet describing what changed and why it matters.
- Mention routes, settings, or breaking changes if any.
```

Cross-link [features.md](./features.md) and [roadmap.md](./roadmap.md) when scope is large.
