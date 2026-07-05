# Modules Reference

Developer/AI reference: routes, permissions, controllers, and key frontend paths.

---

## Navigation ↔ modules

Sidebar defined in `config/navigation.php`. Items filtered by:

1. Module enabled for tenant (`ModuleManager`)
2. User permission (`spatie/laravel-permission`)

---

## Route catalog

### Authentication & shell

| Route name | Method | Path | Controller |
|------------|--------|------|------------|
| `login` | GET | `/login` | AuthController@create |
| `login.store` | POST | `/login` | AuthController@store |
| `logout` | POST | `/logout` | AuthController@destroy |
| `locale.switch` | GET | `/locale/{locale}` | Closure |
| `dashboard` | GET | `/` | DashboardController@index |
| `dashboard.refresh` | POST | `/dashboard/refresh` | DashboardController@refresh |
| `branch-context.update` | POST | `/branch-context` | BranchContextController |
| `preferences.layout` | POST | `/preferences/layout` | PreferenceController |
| `links.index` | GET | `/links` | LinksController (tablet home) |
| `notifications.summary` | GET | `/notifications/summary` | NotificationSummaryController |
| `notifications.read` | POST | `/notifications/{id}/read` | NotificationActionController |
| `notifications.read-all` | POST | `/notifications/read-all` | NotificationActionController |

### Sales

| Route name | Permission | Page |
|------------|------------|------|
| `sales.index` | `invoices.view` | `Sales/Index.vue` |
| `sales.store` | `invoices.create` | |
| `sales.draft` | `invoices.create` | Hold draft |
| `sales.post` | `invoices.create` | Post draft |
| `sales.draft.discard` | `invoices.create` | |
| `sales.payment` | `payments.create` | |
| `sales.void` | `invoices.delete` | |
| `invoices.print` | `invoices.view` | HTML print view |
| `invoices.pdf` | `invoices.view` | PDF download |

Controller: `SalesController` · Service: `SalesService`

### Products & master data

| Module | Route prefix | Permission | Page |
|--------|--------------|------------|------|
| Products | `products.*` | `products.view` | `Products/Index.vue` |
| Categories | `categories.*` | `categories.manage` | `Categories/Index.vue` |
| Brands | `brands.*` | `brands.manage` | `Brands/Index.vue` |
| Customers | `customers.*` | `customers.view` | `Customers/Index.vue` |
| Suppliers | `suppliers.*` | `suppliers.view` | `Suppliers/Index.vue` |

### Inventory

| Route | Permission | Page |
|-------|------------|------|
| `inventory.index` | `inventory.view` | `Inventory/Index.vue` |
| `inventory.receive` | `inventory.receive` | |
| `inventory.adjust` | `inventory.adjust` | |
| `transfers.*` | `inventory.view` | `Transfers/Index.vue` |

Services: `InventoryService`, `StockTransferService`

### Purchasing

| Route | Permission | Page |
|-------|------------|------|
| `purchases.*` | `purchases.view` | `Purchases/Index.vue` |

Actions: `place`, `receive`, `cancel`, `payment` · Service: `PurchaseService`

### Debts & expenses

| Route | Permission | Page |
|-------|------------|------|
| `debts.*` | `debts.view` | `Debts/Index.vue` |
| `expenses.*` | `expenses.view` | `Expenses/Index.vue` |
| `expense-categories.*` | `expenses.view` | `ExpenseCategories/Index.vue` |

### Shipping

| Route | Permission | Page |
|-------|------------|------|
| `shipments.*` | `shipping.view` | `Shipments/Index.vue` |
| `logistics.*` | `shipping.view` | `Logistics/Index.vue` |

### Reports & analytics

| Route | Permission | Page |
|-------|------------|------|
| `reports.index` | `reports.view` | `Reports/Index.vue` |
| `reports.export` | `reports.view` | CSV/PDF |
| `analytics.*` | `analytics.view` | `Analytics/Index.vue` |

### Administration

| Route | Permission | Page |
|-------|------------|------|
| `branches.*` | `branches.manage` | `Branches/Index.vue` |
| `users.*` | `users.manage` | `Users/Index.vue` |
| `settings.*` | `settings.manage` | `Settings/Index.vue` |
| `settings.invoice.preview` | `settings.manage` | Invoice design preview HTML |
| `shop.settings` | `settings.manage` | `Shop/Settings.vue` |
| `data-tools.*` | `settings.manage` | `DataTools/Index.vue` |
| `notifications.index` | auth | `Notifications/Index.vue` |

### Public shop (guest)

| Route | Path | Page |
|-------|------|------|
| `shop.index` | `/shop/{tenant:slug}` | `Shop/Index.vue` |
| `shop.show` | `/shop/{tenant:slug}/products/{product}` | `Shop/Show.vue` |

Middleware: `ResolveShopTenant`

### Platform

| Route | Middleware | Page |
|-------|------------|------|
| `platform.*` | `super_admin` | `Platform/*` |

---

## Key support classes

| Class | File | Role |
|-------|------|------|
| `TenantMoney` | `app/Support/TenantMoney.php` | Exchange rate & base currency from settings |
| `InvoiceDesign` | `app/Support/InvoiceDesign.php` | Invoice template registry |
| `Navigation` | `app/Support/Navigation.php` | Menu builder |
| `Money` | `app/Support/Money.php` | Formatting |

---

## Config files

| File | Purpose |
|------|---------|
| `config/navigation.php` | Sidebar items |
| `config/permissions.php` | Permission catalog |
| `config/invoice.php` | Invoice design definitions |
| `config/money.php` | Default currency env fallbacks |

---

## Translation groups

`lang/{ar,en}/` — `common`, `nav`, `sales`, `purchasing`, `inventory`, `debts`, `settings`, `shop`, `notifications`, `links`, etc.

Client access: `useTrans().t('group.key')`
