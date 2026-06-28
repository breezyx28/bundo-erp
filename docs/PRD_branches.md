# Wholesale Shoe Store ERP System
## Final System Design Summary (with Multi-Branch Support)

**Document Type:** System Requirements Summary  
**Version:** 2.0  
**Date:** June 2026  
**Purpose:** This document summarizes all system requirements, decisions, and features including multi-branch support where each branch operates as an independent business unit with its own users, inventory, and reports.

---

## Executive Summary

The system is a comprehensive ERP solution for wholesale shoe businesses, fully customized for the Sudanese market, with white-label capabilities and multi-branch support. Each branch functions as an independent business unit with separate inventory, users, sales, and financials, while enabling consolidated reporting for headquarters.

**Key Differentiators:**
- **Multi-Branch Ready:** Each branch operates independently with its own inventory, users, and reports
- **White-Label:** Can be rebranded and sold to multiple businesses
- **Sudan-Specific:** Handles erratic pricing, multi-currency, no online payments
- **Batch Tracking:** Accurate COGS with historical cost tracking
- **Flexible:** Custom fields, module enable/disable, adaptable workflows

---

## 1. Multi-Branch Architecture

### 1.1 Branch Concept

Each branch operates as an independent business unit with complete data isolation for transactions while sharing master data.

| Data Type | Shared Across Branches | Branch-Specific |
|-----------|------------------------|-----------------|
| Products (Catalog) | ✓ Shared | - |
| Categories & Brands | ✓ Shared | - |
| Customers | ✓ Shared | - |
| Suppliers | ✓ Shared | - |
| Inventory | - | ✓ Each branch |
| Sales | - | ✓ Each branch |
| Purchases | - | ✓ Each branch |
| Expenses | - | ✓ Each branch |
| Users | - | ✓ Each branch |
| Debts | - | ✓ Each branch |
| Reports | - | ✓ Each branch |

### 1.2 Branch Features

| Feature | Description |
|---------|-------------|
| **Branch Management** | Add, edit, suspend branches with full details |
| **Independent Inventory** | Each branch maintains its own stock |
| **Stock Transfers** | Transfer stock between branches with approval workflow |
| **Branch Users** | Users assigned to specific branches with branch-specific roles |
| **Branch Sales** | Sales recorded per branch with branch-specific invoices |
| **Branch Reports** | Reports per branch and consolidated for HQ |
| **Branch Comparison** | Compare performance across branches |
| **Branch Branding** | Each branch can have its own logo and settings |

---

## 2. Inventory & Product Structure

### 2.1 Product Management

**Product Catalog (Shared Across Branches):**
- Products are shared globally across all branches
- Categories and brands are shared globally
- Product variants (size, color, SKU, barcode) are shared

**Branch-Specific Inventory:**
- Each branch has its own stock quantities
- Branch-specific stock levels and alerts
- Branch-specific stock valuation

### 2.2 Bulk Entry & Reception

The system supports multiple entry cases:

| Case | Description |
|------|-------------|
| **Case 1** | Entire batch with mixed brands and sizes (user enters products individually) |
| **Case 2** | Entire batch with same brand and size (user enters in bulk with total cost) |

- During bulk reception, the user enters a **Total Cost** for the entire batch
- The system automatically calculates the **Per-Unit Cost**
- Stock is added to the **specific branch** receiving the goods

### 2.3 Package Definition

- A "Package" is a **group of products** that the customer selects manually during the sale
- It is not a pre-defined bundle
- Packages are branch-specific or shared (configurable)

### 2.4 Costing Method for Profit (Batch Tracking)

The system tracks **historical prices** for each product with batch-level tracking:

| Stock Age | Cost |
|-----------|------|
| Old Stock (3 months ago) | 500 SDG |
| Medium Stock (2 months ago) | 600 SDG |
| New Stock (Now) | 700 SDG |

- When a sale is made, the system uses the **specific cost** associated with the batch being sold
- This ensures accurate COGS (Cost of Goods Sold) and profit margins
- Each batch is linked to a **specific branch**

**Implementation Notes:**
- **Batch Tracking** system where each batch has its own cost price
- **FIFO (First-In, First-Out)** or manual batch selection
- User can enter prices in **USD** alongside SDG
- Batches are branch-specific

---

## 3. Pricing & Discounts

### 3.1 Strict Pricing Policy

| Rule | Description |
|------|-------------|
| **Base Price** | Set by Admin only |
| **Price Changes** | Admin only, creates historical record |
| **Seller Action** | CANNOT change base price |
| **Seller Action** | Can only apply discounts |

### 3.2 Discount Options

The system supports all discount types:

| Discount Type | Description | Who Can Apply |
|---------------|-------------|---------------|
| **Percentage Discount** | e.g., 10% off | Sales Staff |
| **Fixed Amount Discount** | e.g., 500 SDG off | Sales Staff |
| **Manual Price Override** | Change final price | Admin Only |

### 3.3 Branch-Specific Pricing (Optional)

| Feature | Description |
|---------|-------------|
| **Branch Pricing** | Different prices per branch (optional) |
| **Branch Discounts** | Branch-specific discount policies |
| **Customer-Specific Pricing** | Per customer across branches |

---

## 4. Debt & Customer Management

### 4.1 Customer Management

**Shared Customer Database:**
- Customers are shared across all branches
- Customer can buy from any branch
- Customer can have preferred/default branch

**Quick Customer Feature:**
- **Required:** Name, Phone Number
- **Optional:** Email, WhatsApp Number
- Allows unregistered customers to buy on debt
- Quick customers can be upgraded to full accounts

### 4.2 Debt Management

| Feature | Description |
|---------|-------------|
| **Branch-Specific Debt** | Debt owed to specific branch |
| **Consolidated Debt** | Combined view of all branch debts |
| **Customer Balance** | Total debt across all branches |
| **Branch Statement** | Statement per branch |

### 4.3 Debt Overdue Calculation

**Both options are available:**

| Option | Description |
|--------|-------------|
| **Option A** | Based on Invoice Date + 30 days |
| **Option B** | Based on specific Due Date set by user |

### 4.4 Debt Aging Report

The system provides a Debt Aging Report with the following categories:

| Aging Category | Description |
|----------------|-------------|
| **Current (0-30 days)** | Not yet due |
| **Overdue (30-60 days)** | 30-60 days past due |
| **Overdue (60-90 days)** | 60-90 days past due |
| **Overdue (90+ days)** | More than 90 days past due |

- Reports available **per branch** and **consolidated**
- Helps prioritize collections across branches

---

## 5. Currency & Exchange Rate

### 5.1 Multi-Currency Support

| Feature | Description |
|---------|-------------|
| **Primary Currency** | SDG (Sudanese Pound) |
| **Secondary Currency** | USD (US Dollar) |
| **Additional Currencies** | Optional (future) |
| **Exchange Rate** | Set manually by Admin |
| **Rate Updates** | Can change at any time |

### 5.2 Historical Exchange Rate Tracking

- The system stores the **exchange rate at the time of each transaction**
- Ensures accurate historical reporting and profit calculation
- Branch-specific or global rate tracking (configurable)

---

## 6. Shipping & Undo Logic

### 6.1 Shipping Management

**Features:**
- Create shipping requests from sales invoices
- Track shipment status
- Manage logistics companies per branch
- Track shipping costs per branch

### 6.2 Undo Logic

| Feature | Description |
|---------|-------------|
| **Approved Method** | Create a **"Return from Shipment"** document |
| **Audit Trail** | Creates clear audit trail |
| **Inventory Update** | Adds missing items back to inventory |

### 6.3 Shipping Costs - Deduction Timing

**Both options are supported:**

| Option | Description |
|--------|-------------|
| **Option A (Per-Invoice)** | Shipping cost deducted from the profit of the specific sale invoice |
| **Option B (Global)** | Shipping costs collected and deducted globally |

**Default Method:** Admin sets the default method in system settings per branch or globally.

---

## 7. User Roles & Permissions (with Branches)

### 7.1 User Assignment

| Assignment Type | Description |
|-----------------|-------------|
| **Single Branch** | User works at one branch only |
| **Multiple Branches** | User works at multiple branches (area manager) |
| **Headquarters** | User works at HQ, can view all branches |
| **Super Admin** | Can access all branches and tenants |

### 7.2 Final User Roles

| Role | Permissions | Branch Scope |
|------|-------------|--------------|
| **Super Admin (System Owner)** | Full access: tenants, settings, all branches, user management, financial reports, delete/undo transactions, price changes | All Branches |
| **Admin (Branch Manager)** | Full access for their branch: user management, settings, financial reports, price changes | Own Branch Only |
| **Accountant** | Financial modules: Expenses, Revenue, Profit/Loss, Debt Aging. Can view sales/purchases but CANNOT change prices | Assigned Branch(es) |
| **Warehouse Keeper** | Inventory Management only: Receive stock, transfer stock, view stock levels. CANNOT see selling prices or financial data | Assigned Branch(es) |
| **Sales Staff** | Sales and Customer modules: Create invoices, apply discounts. CANNOT see purchase cost. Can view customer debt | Assigned Branch(es) |
| **Area Manager** | Oversee multiple branches, consolidated reporting | Multiple Branches |
| **HQ Staff** | View all branches, consolidated reports | All Branches |

### 7.3 Sales Staff - Debt Visibility

- **Approved:** Sales Staff can see customer debt when creating invoices
- This helps them make informed decisions about credit sales
- Debt visibility includes debt from any branch (consolidated) or branch-specific (configurable)

### 7.4 Branch-Specific Permissions

| Permission | Scope | Description |
|------------|-------|-------------|
| **view_branch_inventory** | Branch | View inventory at own branch |
| **manage_branch_inventory** | Branch | Manage inventory at own branch |
| **view_all_inventory** | HQ/Area | View all branch inventory |
| **transfer_stock** | Branch | Transfer stock to/from branches |
| **view_branch_sales** | Branch | View own branch sales |
| **view_all_sales** | HQ/Area | View all branch sales |
| **view_branch_financials** | Branch | View own branch financials |
| **view_all_financials** | HQ/Area | View all branch financials |

---

## 8. Reports & KPIs (with Branches)

### 8.1 Dashboard KPIs

The dashboard displays KPIs based on the user's branch access:

| # | KPI | Description | Branch Scope |
|---|-----|-------------|--------------|
| 1 | **Total Revenue** | Today / This Month / This Year (in SDG & USD) | Per Branch / Consolidated |
| 2 | **Total Expenses** | Today / This Month / This Year | Per Branch / Consolidated |
| 3 | **Net Profit** | Today / This Month / This Year | Per Branch / Consolidated |
| 4 | **Total Outstanding Debt** | Customer receivables (Current + Overdue) | Per Branch / Consolidated |
| 5 | **Low Stock Alert** | Products below reorder point | Per Branch |
| 6 | **Top 5 Best-Selling Products** | Based on quantity sold | Per Branch / Consolidated |
| 7 | **Top 5 Best-Selling Brands** | Based on revenue | Per Branch / Consolidated |
| 8 | **Overdue Debt Aging** | Quick view of overdue amounts (30, 60, 90+ days) | Per Branch / Consolidated |
| 9 | **Recent Transactions** | Latest 10 sales/purchases | Per Branch / Consolidated |
| 10 | **Shipping Performance** | Pending shipments / Completed shipments | Per Branch / Consolidated |

### 8.2 Branch Reports

| Report | Description | Scope |
|--------|-------------|-------|
| **Branch P&L** | Profit & Loss per branch | Per Branch |
| **Branch Revenue** | Revenue per branch | Per Branch |
| **Branch Expenses** | Expenses per branch | Per Branch |
| **Branch Debt** | Debt per branch | Per Branch |
| **Branch Cash Flow** | Cash flow per branch | Per Branch |
| **Branch Sales** | Sales per branch | Per Branch |
| **Branch Purchases** | Purchases per branch | Per Branch |
| **Branch Stock Report** | Stock levels per branch | Per Branch |
| **Branch Stock Value** | Valuation per branch | Per Branch |
| **Branch Low Stock** | Low stock items per branch | Per Branch |
| **Branch Transfer History** | All transfers in/out | Per Branch |

### 8.3 Consolidated Reports (HQ)

| Report | Description |
|--------|-------------|
| **Consolidated P&L** | All branches combined |
| **Consolidated Revenue** | Total revenue all branches |
| **Consolidated Expenses** | Total expenses all branches |
| **Consolidated Debt** | Total debt all branches |
| **Multi-Branch Comparison** | Compare branches side-by-side |
| **Branch Performance Ranking** | Performance ranking of branches |

### 8.4 Periodic Profit Comparison

- **Confirmed:** The system provides a side-by-side comparison of:
  - Revenue
  - Cost of Goods Sold (COGS)
  - Expenses
  - Net Profit
- Periods: 30 days, 3 months, 1 year, custom date ranges
- View options: **Both SDG and USD**
- Filter options: **Per Branch, Multiple Branches, Consolidated**

### 8.5 Data Analysis & Prediction

- **Added Feature:** The system includes a **Data Analysis & Prediction** module
- This analyzes historical data to:
  - Predict future sales based on trends
  - Identify seasonal patterns
  - Recommend optimal stock levels
  - Provide insights on which products/brands/categories are likely to perform well
- **Branch Scope:** Analysis available per branch and consolidated

---

## 9. System Architecture

### 9.1 Database Structure

**Core Tables:**

| Table | Description | Branch Scope |
|-------|-------------|--------------|
| `branches` | Branch management | Global |
| `branch_users` | User-branch assignment | Global |
| `products` | Product information, category, brand | Shared |
| `product_variants` | Size, color, etc. | Shared |
| `product_batches` | Batch tracking with cost price, purchase date, branch | Per Branch |
| `suppliers` | Supplier information | Shared |
| `customers` | Full accounts + quick customers | Shared |
| `purchase_orders` | Purchase orders | Per Branch |
| `purchase_order_items` | Purchase order items | Per Branch |
| `sales_invoices` | Sales invoices | Per Branch |
| `sales_invoice_items` | Sales invoice items | Per Branch |
| `shipments` | Shipping information | Per Branch |
| `shipment_returns` | Return documents | Per Branch |
| `product_transfers` | Branch-to-branch transfers | Global |
| `expenses` | Expense tracking | Per Branch |
| `payments` | Customer payments, supplier payments | Per Branch |
| `exchange_rates` | Historical rate tracking | Global |
| `users` & `user_roles` | User management | Per Branch / Global |

### 9.2 Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Backend** | Laravel | 11.x |
| **PHP** | PHP | 8.2+ |
| **Frontend** | Livewire 3 + Alpine.js | 3.x |
| **Database** | MySQL | 8.0+ |
| **Cache/Queue** | Redis | 7.0+ |
| **Web Server** | Apache (cPanel compatible) | 2.4+ |
| **Reporting** | Chart.js / ApexCharts | Latest |
| **Analytics** | Custom data analysis engine | - |

### 9.3 Key Features to Implement

| Feature | Description | Branch Support |
|---------|-------------|----------------|
| **Multi-Branch** | Complete branch isolation and consolidation | ✓ Built-in |
| **Batch Tracking** | Accurate COGS with historical cost tracking | Per Branch |
| **Multi-Currency** | SDG & USD support with historical rates | Global |
| **Debt Aging Reports** | 30/60/90+ days aging | Per Branch / Consolidated |
| **Quick Customer** | Minimal customer creation | Shared |
| **Flexible Discount Engine** | Percentage, fixed amount | Per Branch / Global |
| **Shipping & Logistics** | Complete shipping management | Per Branch |
| **Stock Transfers** | Branch-to-branch transfers | Global |
| **Data Analysis & Prediction** | Predictive analytics | Per Branch / Consolidated |
| **Audit Trail** | All critical actions logged | Per Branch / Global |
| **White-Label** | Rebrandable for multiple businesses | Tenant-Level |

---

## 10. System Customization

### 10.1 Module Enable/Disable

| Module | Description | Default |
|--------|-------------|---------|
| **Dashboard** | Main dashboard with KPIs | Required |
| **Product Management** | Manage products and variants | Required |
| **Inventory Management** | Manage stock | Required |
| **Branch Management** | Manage multiple branches | Optional |
| **Supplier Management** | Manage suppliers | Required |
| **Purchase Management** | Manage purchases | Required |
| **Customer Management** | Manage customers | Required |
| **Sales Management** | Process sales | Required |
| **Debt & Collections** | Manage customer debts | Required |
| **Expense Management** | Track expenses | Required |
| **Shipping & Logistics** | Manage shipping | Optional |
| **Financial Reports** | Advanced reports | Optional |
| **Data Analysis** | Predictive analytics | Optional |
| **Stock Transfers** | Branch-to-branch transfers | Optional (if multi-branch) |

### 10.2 Custom Fields

- Add custom fields to Products, Customers, Invoices, Branches
- Field types: Text, Number, Date, Dropdown, Multi-select, Textarea, File Upload
- Mark fields as required or optional
- Set validation rules per field
- Searchable and reportable custom fields

---

## 11. Summary of Key Features

### 11.1 Core System Features

- [x] Multi-tenant architecture (white-label)
- [x] Multi-branch support (independent branches)
- [x] Role-based permissions with branch scope
- [x] User management per branch
- [x] Product management with variants
- [x] Batch tracking for accurate COGS
- [x] Inventory management per branch
- [x] Stock transfers between branches
- [x] Supplier management
- [x] Purchase order management
- [x] Customer management (including Quick Customer)
- [x] Sales management with flexible discounts
- [x] Debt management and collections
- [x] Debt aging reports (per branch and consolidated)
- [x] Expense management
- [x] Shipping and logistics
- [x] Financial reports (per branch and consolidated)
- [x] Branch performance comparison
- [x] Profit & Loss statement
- [x] Data analysis and prediction
- [x] Import/Export (CSV, Excel)
- [x] Backup and restore
- [x] Notifications (in-app, email)
- [x] Real-time live search
- [x] Multi-language (Arabic, English)
- [x] Custom fields
- [x] Module enable/disable
- [x] Caching strategy
- [x] Audit trail
- [x] White-label branding

### 11.2 Sudan-Specific Features

- [x] Handles unpredictable pricing changes
- [x] Supports multiple currencies (SDG & USD)
- [x] Manages debt flexibly
- [x] Works without online payment gateways
- [x] Handles carton/package-based sales
- [x] Supports batch tracking for accurate profit calculation
- [x] Arabic language (RTL) and English (LTR)
- [x] Local date/time formats
- [x] Sudan-specific business practices

### 11.3 Multi-Branch Features

- [x] Branch management (add, edit, suspend)
- [x] Independent inventory per branch
- [x] Stock transfers between branches
- [x] Users assigned to specific branches
- [x] Branch-specific sales and purchases
- [x] Branch-specific expenses
- [x] Branch-specific shipping
- [x] Branch-specific debt management
- [x] Branch-specific reports
- [x] Consolidated reports for HQ
- [x] Branch performance comparison
- [x] Branch branding customization

---

## 12. Final Notes

### 12.1 System Strengths

- **Fully Customized:** Tailored for Sudanese market conditions
- **White-Label Ready:** Can be rebranded and sold to multiple businesses
- **Multi-Branch:** Complete independent branch operations with consolidation
- **Flexible:** Handles multiple cases and scenarios
- **Accurate:** Batch tracking for precise profit calculation
- **Scalable:** Grows with the business
- **Secure:** Role-based permissions with branch scope

### 12.2 Business Benefits

1. **Centralized Operations:** All branches managed from one system
2. **Real-Time Visibility:** Complete view of all branch performance
3. **Accurate Profit Tracking:** Batch-level COGS for precise margins
4. **Better Collections:** Debt aging reports per branch
5. **Data-Driven Decisions:** Branch comparison and analytics
6. **Scalable:** Add new branches easily
7. **Flexible:** Adapts to unique business needs
8. **Efficient:** Streamlined workflows and automation

### 12.3 Next Steps

With all requirements fully defined, the next steps are:

1. **User Journey Map:** Detailed step-by-step workflows for each role and branch
2. **Entity Relationship Diagram (ERD):** Database structure including branch tables
3. **System Architecture Design:** Technical specifications for multi-branch
4. **User Interface Wireframes:** Mockups for branch context and filters
5. **Development Timeline & Cost Estimation:** Project planning

---

## 13. Appendix: Branch Context UI

### 13.1 Branch Selector

The system will feature a **branch selector** in the header:

```
[Logo] [Branch Selector ▼] [Search] [Notifications] [Profile]
```

**Branch Selector Dropdown:**
```
- Branch A (Current)
- Branch B
- Branch C
- All Branches (HQ view)
```

### 13.2 Branch Filters

All screens will have **branch filters**:

| Screen | Filter Options |
|--------|----------------|
| **Dashboard** | Single branch / All branches |
| **Products** | Branch stock view |
| **Sales** | Branch sales view |
| **Customers** | Branch customer view |
| **Inventory** | Branch stock view |
| **Reports** | Branch comparison view |

### 13.3 User Views by Role

| User Type | Default View |
|-----------|--------------|
| **Branch User** | Their branch only |
| **Area Manager** | Multiple branches |
| **HQ Admin** | All branches |
| **Super Admin** | All tenants and branches |

---

**End of Document**

---

*This document provides the complete system design summary including multi-branch support. The system is now fully defined and ready for development.*

---

**© 2026 - All Rights Reserved**