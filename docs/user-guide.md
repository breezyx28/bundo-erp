# User Guide

Practical how-to for daily users. Assumes you are logged in with appropriate permissions.

---

## Getting started

### Log in
- Open `/login`, enter email and password.
- After login you land on **Dashboard** (or **Links** in tablet mode).

### Switch branch
- Use the **branch dropdown** in the top bar.
- Choose a specific branch or **All branches** (if permitted).
- Sales, inventory, and reports respect the active branch.

### Change language
- Click **AR** or **EN** in the top bar.
- Page reloads with Arabic (RTL) or English (LTR).

### Search
- Click the **magnifying glass** or press **Ctrl+K** to open the command palette.
- Type to jump to pages or actions.

---

## Dashboard

- View today/month/year revenue, expenses, profit.
- See outstanding receivables and aging summary.
- Low stock count and recent activity.
- Use **Refresh** to clear cached KPIs after major changes.

---

## Sales

### Create a sale
1. Go to **Sales** → **New sale**.
2. Add products and quantities.
3. Choose **Cash** or **Credit** (credit requires a customer).
4. Set discount, exchange rate, payment if cash.
5. **Save** to post the invoice.

### Hold an order (server draft)
- Use **Hold** to save an incomplete sale on the server.
- Resume from the drafts list on the Sales page.
- **Post** when ready or **Discard** to delete.

### Unfinished form reminder
- If you leave the sale form without submitting, a **document badge** appears.
- Click it → **Continue** to restore your work.

### Print invoice
- Open invoice detail → **Print** (browser dialog) or **PDF** (download).

---

## Products

- Add/edit products with SKU, prices, category, brand.
- Upload images via the media picker.
- Set **Reorder level** for low-stock alerts.
- For the public shop: enable **Show on shop** and optionally **Featured**.

---

## Inventory

- **Receive stock:** add quantity and cost for a product at current branch.
- **Adjust stock:** set absolute on-hand quantity (records adjustment reason).
- **Movements:** view history per product.
- **Stock transfers:** move stock between branches (see [user-flows.md](./user-flows.md)).

---

## Customers & debts

- Maintain customer records under **Customers**.
- **Debts** shows who owes money and aging buckets.
- **Collect** records a payment; it applies to oldest invoices first.
- **Statement** shows invoice history for a customer.

---

## Purchases

- Create a **purchase order** with supplier and line items.
- **Place** the order when sent to supplier.
- **Receive** goods as they arrive (partial or full).
- **Payment** records supplier payment.

---

## Expenses

- Define categories under **Expense categories** (if shown).
- Record expenses with date, amount, category, notes.

---

## Shipping

- Create shipments linked to invoices or standalone.
- Update status as goods move.
- Mark **Delivered** with optional proof photo.
- Register **Returns** when goods come back.

---

## Reports & analytics

- **Reports:** P&L, cash flow, branch comparison — pick date range and currency.
- **Export** to CSV or PDF.
- **Analytics:** forecasts and performance charts (read-only insights).

---

## Settings

Path: **Settings** (sidebar).

| Tab | What to configure |
|-----|---------------------|
| **General** | Company name, language, timezone |
| **Branding** | Logo, brand colors |
| **Currency** | SDG/USD default, **exchange rate** |
| **Invoice** | Design template, prefix, footer |

### Shop settings
Path: **Settings** → **Shop settings** button, or `/settings/shop`.

1. Copy or open your **public shop link** (`/shop/your-slug`).
2. Enable **Public shop** and enter **Hero title**.
3. Fill contact details (WhatsApp, phone, etc.).
4. Add up to 6 promotional banners.
5. **Save** — visitors can now browse the catalog.

> Until the shop is enabled and saved, the public link returns “not found” for guests. Logged-in admins can still preview.

---

## Calculator

- Tap the **floating calculator button** (bottom-right, opposite the sidebar).
- Modes: **Standard** keypad, **Tax**, **Discount**, **Margin**, **Currency** (SDG↔USD using tenant exchange rate).
- Use **MC/MR/M+/M−** for memory; copy results with the clipboard icon.

---

## Display & accessibility

Path: Profile menu → **Display & accessibility**, or `/preferences/display`.

1. Choose **interface size** (Small → Extra large).
2. Optionally set **body** and **muted** text colors.
3. Enable **High contrast** for stronger readability.
4. **Save** — settings apply immediately and persist per user.

---

## Notifications

- **Bell icon:** recent alerts; mark read or view all.
- **Notifications page:** filter all/unread; manage preferences.
- **Sound alerts:** toggle on/off (plays a tone when new unread arrive).
- **Email alerts:** optional email for important alerts.

---

## Tablet mode

For counter/tablet use:

1. Open profile menu → enable **Tablet mode**.
2. Home becomes an **app icon grid** (`/links`).
3. Tap the **floating grid button** for full menu.
4. Toggle off in profile to return to desktop sidebar layout.

---

## Tips

- **Exchange rate:** Always set the current rate in Settings → Currency before heavy sales/reporting days.
- **Branch:** Confirm the correct branch is selected before sales or stock operations.
- **Permissions:** If a menu item is missing, ask your admin for the right role.
- **Drafts:** Yellow badge = unfinished forms; bell = system notifications.

---

## Getting help

- Administrators manage users and roles under **Users**.
- Data issues may be resolved via **Data tools** (import/export/backup).
- For technical deployment see [DEPLOYMENT.md](./DEPLOYMENT.md).
