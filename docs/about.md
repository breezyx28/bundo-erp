# About Mazin Shoes ERP

## Purpose

Mazin Shoes ERP is a **wholesale shoe retail management system** designed for operations in Sudan. It supports:

- Multiple branches under one company (tenant)
- Carton/package-oriented inventory with batch costing (FIFO)
- Cash and credit sales, collections, and supplier payables
- Dual currency (SDG / USD) with a configurable exchange rate
- Shipping and logistics tracking
- Financial reporting and analytics
- An optional **public product catalog** (marketing shop) per tenant

It is **not** a consumer e-commerce checkout platform. The public shop is a showcase catalog; orders and payments happen inside the ERP.

## Primary users

| Role | Typical tasks |
|------|----------------|
| **Admin** | Settings, users, branches, modules, data tools |
| **Branch manager** | Full branch operations |
| **Salesperson** | Invoices, customers, product lookup |
| **Inventory clerk** | Receive stock, transfers, purchase receiving |
| **Accountant** | Debts, expenses, reports, payments |
| **Viewer** | Read-only access |
| **Super admin** | Platform tenant provisioning (SaaS operator) |

## Business context

- **Wholesale focus:** Large quantities, credit sales, aging receivables
- **Exchange rate:** Tenant setting (e.g. 4900 SDG/USD) drives UI and invoice fallbacks — not a hardcoded default
- **Branch isolation:** Operational data (sales, stock movements) scoped to active branch; catalog data shared at tenant level
- **Consolidated view:** Admins can view all branches from the topbar selector

## Terminology

| Term | Meaning |
|------|---------|
| **Tenant** | One company / organization (e.g. Mazin Shoes) |
| **Branch** | Physical store or warehouse location |
| **Invoice / sale** | Posted sales document; deducts stock |
| **Draft / hold sale** | Server-side sales draft before posting |
| **Form draft** | Browser localStorage unfinished form (UX convenience) |
| **PO** | Purchase order |
| **Aging** | Receivables grouped by overdue buckets (current, 30, 60, 90+ days) |
| **Shop** | Public catalog at `/shop/{slug}` |

## Languages

- **Arabic (`ar`):** Default; RTL layout
- **English (`en`):** LTR layout
- Switch via topbar **AR / EN** (full page reload updates direction)

## Related legacy docs

Detailed original requirements live in:

- [PRD_branches.md](./PRD_branches.md)
- [user_flow_branches.md](./user_flow_branches.md)
- [database_schemas_branches.md](./database_schemas_branches.md)
