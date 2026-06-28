
# MZS Shoes ERP — Design System Redesign Plan

A full redesign plan for the multi-branch wholesale shoe ERP. The goal is a calm, premium, data-dense interface that reflects the **MZS brand** (deep emerald + warm gold on cream) and works equally well in **Arabic (RTL)** and **English (LTR)** for 7 distinct roles across many branches.

---

## 1. Design Principles

1. **Premium but operational** — the logo signals heritage/luxury, but this is a daily ERP. Brand colors live in chrome (sidebar, headers, accents); content surfaces stay neutral so data is readable for 8+ hour shifts.
2. **Bilingual-first** — every component designed RTL and LTR simultaneously. No mirrored icons for numerals, charts, or directional flow indicators (stock transfer arrows follow logical, not visual, direction).
3. **Branch context is always visible** — a persistent branch chip in the header is the anchor of the whole UI; color-coded so users never post a sale to the wrong branch.
4. **Role-shaped density** — Sales Staff sees a POS-like big-tap layout; Accountant/HQ sees dense tables. Same tokens, different density presets.
5. **Money is sacred** — tabular numerals, currency badges (SDG/USD), positive/negative semantic colors, never rely on color alone.
6. **Offline-tolerant feel** — clear sync/save states, optimistic UI, explicit "saved" confirmations (Sudan connectivity reality).

---

## 2. Brand & Color System

Pulled from the logo: deep forest green field, antique gold mark, cream Arabic wordmark.

### Core palette (tenant-overridable)
```text
Brand
  --brand-emerald-900  #0E2E22   (sidebar, primary surfaces)
  --brand-emerald-800  #14402F   (header, hover)
  --brand-emerald-600  #1F6B4C   (primary actions, links)
  --brand-emerald-50   #ECF4EF   (selected rows, subtle bg)

  --brand-gold-600     #B8893B   (primary CTA, key metrics)
  --brand-gold-500     #C9A24C   (logo gold, badges)
  --brand-gold-100     #F5E9CC   (highlight bg, gold chips)

  --brand-cream        #F6F1E4   (logo wordmark, soft accents)

Neutral (content)
  --bg                 #FAFAF7   (app bg, warm off-white)
  --surface            #FFFFFF   (cards, tables)
  --surface-muted      #F3F1EC
  --border             #E6E2D8
  --text               #1A1F1C
  --text-muted         #5B655F

Semantic
  --success  #1F7A4D    --success-bg  #E6F3EC
  --warning  #B8761F    --warning-bg  #FBEFD8   (low stock, pending transfers)
  --danger   #B23B3B    --danger-bg   #FBE7E7   (debts overdue, errors)
  --info     #2A5C8A    --info-bg     #E4EEF8

Branch identifiers (auto-assigned, 8 swatches that don't clash with brand)
  Teal #2C7A7B • Indigo #4C51BF • Plum #805AD5 • Rust #C05621
  Olive #6B7A2F • Slate #4A5568 • Rose #B83280 • Ocean #2B6CB0
```

Dark mode: invert neutrals to a warm graphite (`#14160F` bg, `#1C1F18` surface); brand emerald lifts to `#2E8A66`, gold to `#D4B25E` for AA contrast.

White-label: tenants override `--brand-*` only; semantic + neutral tokens are locked so the system always reads correctly.

---

## 3. Typography

Bilingual pairing, both free, both with excellent Arabic + Latin coverage and tabular figures.

```text
Arabic UI:     IBM Plex Sans Arabic   (400, 500, 600, 700)
Latin UI:      Inter                  (400, 500, 600, 700)
Numerics:      Inter "tnum" + "lnum"  (tables, money, KPIs)
Display:       Fraunces 600           (login, empty states, report covers — echoes the logo serif)
Mono:          JetBrains Mono         (SKUs, invoice numbers, codes)
```

Scale (8-pt rhythm, fluid):
```text
display  32 / 40   semibold   Fraunces
h1       24 / 32   semibold
h2       20 / 28   semibold
h3       16 / 24   semibold
body     14 / 22   regular     (default table & form text)
small    13 / 20   regular     (meta, helper)
micro    12 / 16   medium      (badges, table headers — UPPERCASE LTR / normal RTL)
```

RTL rules: never uppercase Arabic; never letter-space Arabic; line-height +2px for Arabic body to fit diacritics.

---

## 4. Spacing, Radius, Elevation

```text
Spacing scale  4 8 12 16 20 24 32 40 56 72
Radius         sm 6   md 10   lg 14   pill 999
Shadows        sm  0 1 2 rgba(20,40,30,.06)
               md  0 4 12 rgba(20,40,30,.08)
               lg  0 12 32 rgba(20,40,30,.10)
Focus ring     2px outline brand-gold-600, 2px offset
```

Density presets (data-density attr on `<body>`):
- `comfortable` — row 48px (default for managers)
- `compact` — row 36px (warehouse, accountant)
- `pos` — row 64px, font +2 (sales staff)

---

## 5. Layout Architecture

```text
┌──────────────────────────────────────────────────────────────┐
│  Top Bar (56px, emerald-900)                                 │
│  [Logo] [Branch ▼ colored] [Global Search ⌘K] [+ Quick] ... │
├──────────┬───────────────────────────────────────────────────┤
│ Sidebar  │  Page Header (breadcrumb · title · primary action)│
│ 240/64px │  ───────────────────────────────────────────────  │
│ emerald  │  Filter bar (chips, date range, branch scope)     │
│ collap.  │  Content (cards · tables · forms)                 │
│          │  ───────────────────────────────────────────────  │
│          │  Sticky footer for bulk actions / totals          │
└──────────┴───────────────────────────────────────────────────┘
```

- Sidebar: collapsible to icon rail; grouped by module (Sales, Inventory, Purchases, Finance, Reports, Admin); active item uses gold left border + emerald-800 bg.
- RTL: full mirror (sidebar on right, breadcrumb chevrons flip, but numerals/charts/transfer-flow arrows stay logical).
- Branch chip in top bar uses the branch's assigned swatch as a 4px underline + dot; "All Branches" (HQ) shows a striped multi-color chip.

---

## 6. Component Library (Tailwind + Blade/Livewire)

All components shipped as Blade components (`<x-mzs.button />`) backed by Tailwind utility classes mapped to tokens. Built on **Tailwind CSS 3** + **Alpine.js** + **Livewire 3**, no external UI kit dependency to keep white-label flexible.

Core inventory:
- **Buttons** — primary (gold), secondary (emerald outline), ghost, danger, icon. Sizes sm/md/lg + POS.
- **Inputs** — text, number-with-stepper, currency (with SDG/USD toggle), searchable select, date (Gregorian + Hijri toggle), file, custom-field renderer.
- **Tables** — sticky header, sticky first column, sticky totals row, column resize, density toggle, saved views, row-level actions menu, bulk selection bar that docks to bottom.
- **Cards** — KPI card (label, value, delta, sparkline, currency badge), entity card, alert card.
- **Badges & Chips** — status (Draft/Confirmed/Paid/Overdue), branch chip, currency chip, stock-level chip.
- **Money component** — `<x-mzs.money :amount :currency />` enforces tabular figures + sign color + thousand separators per locale.
- **Branch selector** — searchable, grouped by region, "All branches" pinned, recent branches, keyboard ⌘B.
- **Empty states** — illustrated with subtle shoe/box line art in gold on cream.
- **Toasts & banners** — top-right (LTR) / top-left (RTL); sticky banner for "Working offline — changes queued".
- **Modals & drawers** — drawers for create/edit (right LTR, left RTL); modals only for confirms.
- **Stepper / wizard** — for multi-step flows (stock transfer, purchase receive).
- **Tabs, accordion, popover, tooltip, command palette (⌘K)**.

---

## 7. Module-Level Redesign Patterns

| Module | Key UX moves |
|---|---|
| **Dashboard** | Role-tailored: Sales Staff = today's sales + quick-sell; Branch Manager = KPIs + low-stock + overdue debts; HQ = branch comparison grid with sparkbars |
| **Products** | Master list shared; per-branch stock shown as horizontal mini-bars across branches in one row |
| **Inventory** | Batch view with cost-per-batch timeline; low-stock heatmap by branch |
| **Stock Transfer** | 3-pane wizard: Source branch → Items (with available qty) → Destination; clear approval status timeline |
| **Sales / POS** | Big-tap mode for sales staff; carton-quantity stepper prominent; "Quick Customer" inline; live total in sticky footer |
| **Debts / AR** | Aging buckets as colored columns; per-customer drawer with payment timeline |
| **Purchases** | Receive flow with batch + cost capture; FX rate stamp at top |
| **Reports** | Filter rail on side (LTR-right/RTL-left); export PDF uses brand cover page |
| **Admin / Branches** | Branch cards with assigned color, manager avatar, KPIs, suspend toggle |

---

## 8. Iconography, Imagery, Motion

- **Icons:** Lucide (outline, 1.5px) for UI; small custom set for shoe-domain (carton, pair, batch, transfer) drawn in same stroke style. Never flip icons in RTL except chevrons & arrows that indicate reading direction.
- **Illustrations:** sparse line art in gold on cream for empty states and onboarding; the logo's shoe-curve motif used as a subtle divider flourish on report covers and login.
- **Motion:** 150ms ease-out for hover/focus; 200ms for drawers; respect `prefers-reduced-motion`. No decorative motion in data tables.

---

## 9. Accessibility & Internationalization

- AA contrast on all token pairs (verified); gold-on-emerald uses `gold-500` on `emerald-900` (passes AA large only — used for branding, not body text).
- Full keyboard nav; visible focus ring (gold) on every interactive element; ⌘K palette for power users.
- Logical-property CSS (`margin-inline-start`, `padding-inline-end`) throughout — one stylesheet serves both directions.
- Numbers: Western digits by default with toggle to Eastern Arabic numerals per user preference; dates show Gregorian + Hijri side-by-side where relevant.
- Currency formatting via `Intl.NumberFormat` with locale + currency code; FX rate visible next to USD amounts.
- Screen-reader labels in user's language; `aria-live` on toasts and sync banners.

---

## 10. Deliverables & Rollout

**Deliverables**
1. `design-tokens.json` (Style Dictionary) → exports to `tokens.css`, `tailwind.config.js`, and a tenant-overrides layer.
2. `tailwind.config.js` mapping all tokens to utilities; `@layer components` for primitives.
3. Blade component library under `resources/views/components/mzs/*` with Livewire-aware variants.
4. Storybook-equivalent **pattern page** at `/_design` (gated to super-admin) showing every component in AR + EN, light + dark, all densities.
5. Updated layouts: `layouts/app.blade.php`, sidebar, top bar, branch selector, page header.
6. Print/PDF stylesheet for invoices, transfer notes, reports (brand cover, tabular figures, bilingual headers).
7. Brand assets: favicon, app icon, login background, PDF cover, email templates.

**Phased rollout (no big-bang)**
1. **Foundations (week 1–2):** tokens, Tailwind config, typography, base layout, sidebar, top bar, branch selector, login. Ship behind a `?ui=v2` flag.
2. **Primitives (week 3):** buttons, inputs, tables, money, badges, modals, drawers, toasts, command palette. Pattern page live.
3. **High-traffic modules (week 4–6):** Dashboard, Sales/POS, Inventory list, Stock Transfer wizard, Debts.
4. **Remaining modules (week 7–9):** Purchases, Customers, Suppliers, Expenses, Shipping, Reports.
5. **Admin & polish (week 10):** Tenant/branch admin, white-label theming UI, dark mode, print templates, accessibility audit, RTL QA across all roles.
6. **Cutover:** flip flag per tenant after sign-off; keep legacy CSS for 1 release as fallback.

---

## 11. Technical Notes (Laravel + Livewire)

- Tokens live in a single `resources/css/tokens.css` imported by Tailwind; tenant overrides injected as a `<style>` block in `app.blade.php` from the tenant's stored brand colors.
- All components are Blade components; interactive ones wrap Alpine + Livewire so they degrade gracefully.
- Branch context stored in session + injected into a global Livewire property so every component can read `$this->branchId` without prop-drilling.
- Direction (`dir="rtl|ltr"`) set on `<html>` from user locale; Tailwind `rtl:` variant + logical properties handle the rest.
- Dark mode via `class="dark"` on `<html>`, persisted per user.

---

Approve this plan and I'll switch to build mode and start with **Phase 1 — Foundations** (tokens, Tailwind config, typography, base layout, branded login, sidebar + top bar + branch selector) behind a feature flag so production stays untouched.
