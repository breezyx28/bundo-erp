# Roadmap

Application direction: what is **done**, **in progress**, and **planned**. Aligns with original PRD phases and post-PRD enhancements.

---

## Vision

A complete wholesale shoe ERP for Sudanese multi-branch retailers: inventory accuracy, credit control, dual currency, optional public catalog, and mobile-friendly branch operations — without unnecessary complexity.

---

## Completed milestones

| Phase | Scope | Status |
|-------|-------|--------|
| **Foundation** | Auth, tenancy, branches, settings, i18n | Done |
| **Master data** | Products, categories, brands, customers, suppliers | Done |
| **Inventory** | Receive, adjust, movements, FIFO batches | Done |
| **Purchasing** | PO lifecycle, receiving, payments | Done |
| **Sales** | Invoices, payments, void, print/PDF | Done |
| **Debts** | Aging, collections, payables | Done |
| **Expenses** | Categories + expenses | Done |
| **Shipping** | Shipments, returns, logistics | Done |
| **Reporting** | P&L, cash flow, branch comparison, export | Done |
| **Analytics** | Forecasts, rankings, optimization views | Done |
| **Platform** | Super admin, tenant provisioning | Done |
| **Stack migration** | Inertia + Vue + Nuxt UI | Done |
| **Tablet mode** | Links launcher, layout preference | Done |
| **Sales drafts** | Hold/post/discard server drafts | Done |
| **Public shop** | Catalog, shop settings, slug URLs | Done |
| **Form drafts** | localStorage UX + reminder badge | Done |
| **Invoice designs** | 3 templates + settings picker | Done |
| **Exchange rate** | Tenant setting everywhere | Done |
| **Notifications** | Polling + sound preference | Done |

---

## Near-term (suggested)

| Item | Rationale |
|------|-----------|
| **Arabic user guide** | Mirror [user-guide.md](./user-guide.md) for end users |
| **Shop SEO & OG tags** | Better sharing on WhatsApp/social |
| **Notification email delivery** | Wire `emailAlerts` preference to mailer |
| **Form draft tests (E2E)** | Browser tests for localStorage flows |
| **README sync** | Update root README to reflect Vue stack |
| **Invoice design thumbnails** | PNG exports alongside SVG covers |

---

## Medium-term (from PRD backlog)

| Item | Notes |
|------|-------|
| **Barcode / label printing** | Warehouse efficiency |
| **Advanced pricing** | Customer tiers, branch price lists |
| **Mobile app (PWA enhancements)** | Offline sales queue |
| **WhatsApp order intake** | Link shop to WhatsApp Business API |
| **Multi-warehouse locations** | Sub-locations within branch |
| **Audit dashboard** | activitylog UI for admins |

---

## Long-term / optional

| Item | Notes |
|------|-------|
| **Online checkout** | Would be separate from catalog-only shop |
| **Manufacturing / assembly** | If vertical expands beyond wholesale |
| **API for integrations** | REST/GraphQL for external systems |
| **SaaS billing** | Stripe for platform subscriptions |

---

## Non-goals (current)

- Consumer e-commerce checkout on public shop
- Real-time WebSocket notifications (polling chosen for simplicity)
- Per-branch separate databases (single DB, tenant scoped)

---

## Phase reference (legacy)

Original phased plan documented in:

- [system_requirement_plan_branches.md](./system_requirement_plan_branches.md)
- [PRD_branches.md](./PRD_branches.md)

Most Phase 2–13 ERP scope is **complete**. Remaining items above are enhancements beyond the original PRD.

---

## Decision log

| Date | Decision | Reason |
|------|----------|--------|
| 2026-06 | Inertia + Vue over Livewire | Richer client UX, tablet mode, form drafts |
| 2026-07 | JSON polling over WebSockets | Lighter ops on PHP-FPM hosting |
| 2026-07 | localStorage form drafts | No server storage; UX convenience only |
| 2026-07 | Shop = catalog only | Wholesale orders stay in ERP |
| 2026-07 | Tenant exchange rate in settings | SDG volatility; 600 env default was wrong for production |

---

*Update this file when prioritizing new work. Mark completed items in [changelog.md](./changelog.md).*
