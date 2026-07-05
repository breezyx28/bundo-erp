# Roles & Permissions

Authorization uses [spatie/laravel-permission](https://github.com/spatie/laravel-permission).

- **Catalog:** `config/permissions.php`
- **Seeder:** `database/seeders/RolesAndPermissionsSeeder.php`
- **Route enforcement:** `->middleware('permission:…')` in `routes/web.php`
- **Frontend checks:** `usePermissions()` composable reads `auth.user.permissions`

---

## Roles

| Role | Description |
|------|-------------|
| `super_admin` | Platform operator; bypasses all gates (`Gate::before`) |
| `admin` | Full tenant access including `branches.view_all` |
| `branch_manager` | Operational lead for assigned branches |
| `accountant` | Financial modules: invoices, debts, expenses, reports |
| `salesperson` | Sales-focused: customers, create invoices, view inventory |
| `inventory_clerk` | Products, inventory, purchase receiving |
| `viewer` | Read-only across permitted modules |

Users may have **multiple roles**. Effective permissions are the union of all role permissions.

---

## Permission groups (summary)

| Group | Examples |
|-------|----------|
| **Platform** | `branches.manage`, `users.manage`, `settings.manage` |
| **Products** | `products.view`, `products.create`, `categories.manage`, `brands.manage` |
| **Inventory** | `inventory.view`, `inventory.receive`, `inventory.adjust` |
| **Purchasing** | `purchases.view`, `purchases.create`, `suppliers.view` |
| **Sales** | `invoices.view`, `invoices.create`, `invoices.delete`, `payments.create` |
| **Debts** | `debts.view`, `debts.manage` |
| **Expenses** | `expenses.view`, `expenses.create` |
| **Shipping** | `shipping.view`, `shipping.manage` |
| **Reports** | `reports.view`, `analytics.view` |

---

## Branch access

- Users are attached to one or more **branches** (`user_branches`).
- `BranchContext` limits queries to allowed branches.
- `branches.view_all` permission enables consolidated multi-branch view.

---

## Module gating

Even with permission, a nav item is hidden if the tenant module is disabled:

`products`, `inventory`, `customers`, `purchases`, `sales`, `debts`, `expenses`, `shipping`, `reports`, `analytics`

Managed via `ModuleManager` / tenant module records.

---

## Public shop

- No authentication required for `/shop/{slug}`.
- Configuration requires `settings.manage`.
- Product visibility uses `products.view` for admin toggles.

---

## Checking permissions in code

**Backend:**
```php
Gate::authorize('invoices.create');
$user->can('settings.manage');
```

**Frontend:**
```js
import { usePermissions } from '@/composables/usePermissions';
const { can } = usePermissions();
if (can('invoices.create')) { … }
```
