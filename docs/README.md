# Mazin Shoes ERP — Documentation Index

Central documentation hub for **Mazin Shoes**, a multi-branch wholesale shoe retail ERP built for the Sudanese market (SDG/USD, exchange-rate volatility, carton/batch inventory).

Use this page as a **sitemap**: pick the document that matches your goal.

---

## Quick links by audience

| I want to… | Read |
|------------|------|
| Understand what the product is | [about.md](./about.md) |
| Learn how to use features (staff/admin) | [user-guide.md](./user-guide.md) |
| Follow business workflows step-by-step | [user-flows.md](./user-flows.md) |
| See everything the app can do | [features.md](./features.md) |
| Know what changed and when | [changelog.md](./changelog.md) |
| See what's done vs planned | [roadmap.md](./roadmap.md) |
| Understand code & stack (developers) | [architecture.md](./architecture.md) |
| Find routes, permissions, modules | [modules-reference.md](./modules-reference.md) |
| Frontend patterns (Vue/Inertia) | [frontend.md](./frontend.md) |
| Quick facts for AI / search | [ai-context.md](./ai-context.md) |
| Deploy to production | [DEPLOYMENT.md](./DEPLOYMENT.md) |
| Original product requirements | [PRD_branches.md](./PRD_branches.md) |

---

## Document map

```
docs/
├── README.md                 ← You are here (index / sitemap)
├── about.md                  Product purpose, audience, terminology
├── architecture.md           Tech stack, layers, tenancy, conventions
├── features.md               Feature catalog (by module)
├── modules-reference.md      Routes, permissions, key files per module
├── user-flows.md             Business process diagrams & steps
├── user-guide.md             End-user how-to (settings, sales, shop, …)
├── roles-and-permissions.md  RBAC matrix
├── frontend.md               Vue 3, Inertia, composables, layouts
├── changelog.md              Implemented changes history
├── roadmap.md                Completed milestones & future direction
├── ai-context.md             Dense reference for AI agents
│
├── PRD_branches.md           (legacy) Product requirements
├── PRD_proposal_branches.md
├── user_flow_branches.md
├── tech_requirements_branches.md
├── system_requirement_plan_branches.md
├── database_schemas_branches.md
├── addendum_docs_branches.md
├── DEPLOYMENT.md
└── STYLES_WORKFLOW.md
```

---

## Application at a glance

| Aspect | Detail |
|--------|--------|
| **Type** | Multi-tenant, multi-branch B2B ERP |
| **Primary users** | Branch managers, sales, inventory, accountants, admins |
| **Public surface** | Optional catalog at `/shop/{tenant-slug}` (no checkout) |
| **Locales** | Arabic (RTL, default) + English (LTR) |
| **Currencies** | SDG base + USD via tenant exchange rate |
| **Auth** | Session login; Spatie roles/permissions |
| **UI** | Laravel + Inertia + Vue 3 + Nuxt UI |

---

## Maintenance

When adding or changing a feature:

1. Update [features.md](./features.md) if user-visible behavior changed.
2. Add an entry to [changelog.md](./changelog.md) with date and summary.
3. Adjust [roadmap.md](./roadmap.md) if scope shifted.
4. Update [modules-reference.md](./modules-reference.md) for new routes/permissions.
5. Refresh [ai-context.md](./ai-context.md) for high-traffic facts agents need.

---

*Last updated: July 2026*
