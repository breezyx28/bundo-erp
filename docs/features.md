# Feature Catalog

User-visible capabilities grouped by area. For routes and permissions see [modules-reference.md](./modules-reference.md).

---

## Core shell

| Feature | Description |
|---------|-------------|
| **Dashboard** | KPIs: revenue, expenses, profit, outstanding debts, low stock, trends |
| **Branch selector** | Switch active branch or consolidated “all branches” view |
| **Notifications bell** | In-app alerts; polls every 30s when tab visible; optional sound |
| **Command palette** | Ctrl+K search across pages/actions |
| **Locale switch** | Arabic / English with RTL/LTR |
| **Tablet mode** | App-icon launcher home (`/links`), FAB navigation, no sidebar |
| **Calculator FAB** | Floating business calculator: tax, discount, margin, SDG↔USD conversion |
| **Display preferences** | Per-user UI scale, text colors, high contrast (`/preferences/display`) |
| **Form drafts** | Auto-save unfinished forms to localStorage; floating reminder badge |
| **PWA shell** | Manifest + service worker for installable/offline shell |

---

## Sales

| Feature | Description |
|---------|-------------|
| **Sales invoices** | Cash/credit; line items; discounts; exchange rate |
| **Payments** | Partial/full payment on invoices |
| **Void** | Cancel posted invoice (permission-gated) |
| **Hold / draft sales** | Server-side draft before posting (`sales.draft` → post/discard) |
| **Invoice print/PDF** | Tenant-selectable design: Classic, Minimal, Compact |
| **Customer credit** | Credit sales update receivables |

---

## Products & catalog

| Feature | Description |
|---------|-------------|
| **Products** | SKU, pricing, cost, variants, images (media library) |
| **Categories & brands** | Master data for classification |
| **Shop visibility** | Per-product `show_in_shop`, `featured_in_shop`, shop description |
| **Low stock alerts** | Notifications when below reorder level |

---

## Inventory

| Feature | Description |
|---------|-------------|
| **Stock levels** | On-hand by branch; consolidated view |
| **Receive stock** | Add inventory with batch/cost |
| **Adjust stock** | Absolute quantity adjustment with reason |
| **Stock movements** | Audit trail per product |
| **Inter-branch transfers** | Request → approve → dispatch → receive |

---

## Purchasing

| Feature | Description |
|---------|-------------|
| **Purchase orders** | Create, edit, place, cancel |
| **Receive goods** | Partial/full receiving against PO lines |
| **Supplier payments** | Record payments against PO balance |
| **Payables** | Visible in debts module |

---

## Customers & debts

| Feature | Description |
|---------|-------------|
| **Customers** | Master data; branch balances |
| **Receivables aging** | Current, 30, 60, 90+ day buckets |
| **Collect payment** | Allocate to oldest invoices first |
| **Statement & remind** | Per-customer statement; payment reminders |
| **Payables tab** | Supplier aging mirror |

---

## Expenses

| Feature | Description |
|---------|-------------|
| **Expense categories** | Configurable categories |
| **Expenses** | Record branch expenses with date/amount/category |

---

## Shipping & logistics

| Feature | Description |
|---------|-------------|
| **Shipments** | Create, status workflow, deliver with POD |
| **Returns** | Register and process shipment returns |
| **Logistics companies** | Carrier master data |
| **Shipping report** | Status/cost summaries |

---

## Reports & analytics

| Feature | Description |
|---------|-------------|
| **P&L report** | Profit and loss by date range |
| **Cash flow** | Cash movement report |
| **Branch comparison** | Cross-branch performance |
| **Export** | CSV/PDF export |
| **Analytics** | Forecast, product/customer performance, branch ranking |

---

## Settings & administration

| Feature | Description |
|---------|-------------|
| **General** | Company name, locale, timezone |
| **Currency** | Default currency (SDG/USD), **exchange rate** |
| **Branding** | Logo, primary/secondary colors |
| **Invoice** | Prefix, footer, **design picker** with preview |
| **Shop settings** | Enable public catalog, hero, banners, contact, share message |
| **Branches** | CRUD branches |
| **Users** | CRUD users, roles, branch access |
| **Data tools** | Import/export Excel, backups |

---

## Public marketing shop

| Feature | Description |
|---------|-------------|
| **Catalog** | `/shop/{slug}` — product grid, categories, featured |
| **Product detail** | Variants, specs, contact CTAs (WhatsApp/call) |
| **Enable/disable** | Shop hidden (404) until enabled and saved |
| **Admin preview** | Logged-in admins can preview disabled shop |
| **Show/hide prices** | Tenant setting |

---

## Platform (super admin)

| Feature | Description |
|---------|-------------|
| **Tenant management** | Create/edit tenants |
| **Enter tenant** | Impersonate tenant context for support |
| **Platform metrics** | Cross-tenant overview |

---

## Notifications

| Type | Examples |
|------|----------|
| **alert** | Low stock |
| **reminder** | Overdue debts |
| **success** | Payment received |
| **info** | General updates |

Preferences: email alerts, sound alerts (Notifications page).

---

## Module toggles

Tenants can enable/disable feature modules via `ModuleManager`:

`products`, `inventory`, `customers`, `purchases`, `sales`, `debts`, `expenses`, `shipping`, `reports`, `analytics`

Disabled modules hide navigation items.
