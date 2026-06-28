# Wholesale Shoe Store ERP System
## System Requirements Specification (SRS) - With Multi-Branch Support

---

## Document Information

| **Project** | Wholesale Shoe Store ERP System |
|-------------|----------------------------------|
| **Client** | Sudan (Wholesale Shoe Business) |
| **Document Type** | System Requirements Specification |
| **Version** | 2.0 |
| **Date** | June 2026 |

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [System Overview](#2-system-overview)
3. [Multi-Branch Architecture](#3-multi-branch-architecture)
4. [User Roles & Permissions](#4-user-roles--permissions)
5. [Module 1: Dashboard](#5-module-1-dashboard)
6. [Module 2: Product Management](#6-module-2-product-management)
7. [Module 3: Inventory Management](#7-module-3-inventory-management)
8. [Module 4: Supplier Management](#8-module-4-supplier-management)
9. [Module 5: Purchase Management](#9-module-5-purchase-management)
10. [Module 6: Customer Management](#10-module-6-customer-management)
11. [Module 7: Sales Management](#11-module-7-sales-management)
12. [Module 8: Accounts Receivable & Collections](#12-module-8-accounts-receivable--collections)
13. [Module 9: Expense Management](#13-module-9-expense-management)
14. [Module 10: Shipping & Logistics](#14-module-10-shipping--logistics)
15. [Module 11: Financial Management & Accounting](#15-module-11-financial-management--accounting)
16. [Module 12: Data Analysis & Prediction](#16-module-12-data-analysis--prediction)
17. [Technical Specifications](#17-technical-specifications)
18. [System Benefits](#18-system-benefits)

---

## 1. Executive Summary

This document outlines the complete requirements for a **Wholesale Shoe Store ERP System** designed specifically for the Sudanese market with **multi-branch support**. The system is tailored to handle the unique challenges of the Sudanese business environment, including:

- **Erratic pricing changes** (weekly or even daily)
- **Multi-currency support** (SDG and USD)
- **No online payment gateways**
- **Carton/package-based sales** (not individual pairs)
- **Flexible debt management**
- **Batch tracking for accurate profit calculation**
- **Multi-branch support** (each branch operates independently with its own inventory, users, and reports)

The system is designed to manage all daily operations across multiple branches from a single centralized platform, organizing workflows, minimizing errors, maximizing profits, and enhancing inventory and customer tracking.

---

## 2. System Overview

### 2.1 Core Objectives

1. **Centralized Operations:** All business processes across all branches in one platform
2. **Error Reduction:** Minimize manual data entry errors
3. **Accurate Inventory:** Real-time stock visibility per branch
4. **Enhanced Management:** Oversight of sales and procurement across branches
5. **Efficient Collections:** Streamlined debt management per branch
6. **Real-Time Profit/Loss:** Immediate profitability insights per branch and consolidated
7. **Data-Driven Decisions:** Detailed reports for strategic planning
8. **Increased Efficiency:** Improved workflow and staff productivity
9. **Branch Autonomy:** Each branch operates independently with its own users and inventory
10. **Consolidated Oversight:** Headquarters can view all branches combined

### 2.2 Key System Characteristics

- **Flexible Entry:** Support for both bulk and individual product entry
- **Batch Tracking:** Historical cost tracking for accurate COGS
- **Multi-Currency:** Full SDG and USD support
- **Offline-First:** Designed for Sudan's environment
- **Mobile-Responsive:** Accessible on phones, tablets, and computers
- **Audit Trail:** Full logging of all critical actions
- **Multi-Branch Ready:** Each branch functions as an independent business unit

### 2.3 System Assumptions

- Internet connectivity is not guaranteed; system can work offline with sync
- Payment gateways are not available or used
- Prices change frequently (weekly/monthly)
- Customers may be unregistered (Quick Customer feature)
- Sales are typically in cartons/packages, not individual pairs
- Each branch operates independently with its own inventory and users
- Headquarters needs consolidated reporting across all branches

---

## 3. Multi-Branch Architecture

### 3.1 Branch Concept

Each branch functions as an independent business unit with complete data isolation for transactions while sharing master data across branches.

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

### 3.2 Branch Features

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

### 3.3 Branch Data Fields

| Field | Description |
|-------|-------------|
| **Branch Name** | Display name of the branch |
| **Branch Code** | Unique identifier for the branch |
| **Address** | Physical address of the branch |
| **Phone** | Contact number for the branch |
| **Email** | Contact email for the branch |
| **Manager** | Branch manager assigned |
| **Is Active** | Branch status (active/suspended) |
| **Settings** | Branch-specific settings (currency, timezone, etc.) |

---

## 4. User Roles & Permissions (with Branches)

### 4.1 User Assignment

| Assignment Type | Description |
|-----------------|-------------|
| **Single Branch** | User works at one branch only |
| **Multiple Branches** | User works at multiple branches (area manager) |
| **Headquarters** | User works at HQ, can view all branches |
| **Super Admin** | Can access all branches and tenants |

### 4.2 Role Definitions

| Role | Permissions | Branch Scope |
| :--- | :--- | :--- |
| **Super Admin (System Owner)** | Full access: tenants, settings, all branches, user management, financial reports, delete/undo transactions, price changes | All Branches |
| **Admin (Branch Manager)** | Full access for their branch: user management, settings, financial reports, price changes | Own Branch Only |
| **Accountant** | Financial modules: Expenses, Revenue, Profit/Loss, Debt Aging. Can view sales/purchases but CANNOT change prices | Assigned Branch(es) |
| **Warehouse Keeper** | Inventory Management only: Receive stock, transfer stock, view stock levels. CANNOT see selling prices or financial data | Assigned Branch(es) |
| **Sales Staff** | Sales and Customer modules: Create invoices, apply discounts. CANNOT see purchase cost. Can view customer debt | Assigned Branch(es) |
| **Area Manager** | Oversee multiple branches, consolidated reporting | Multiple Branches |
| **HQ Staff** | View all branches, consolidated reports | All Branches |

### 4.3 Permission Matrix

| Feature | Super Admin | Branch Admin | Accountant | Warehouse Keeper | Sales Staff |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Branch Management | ✅ | ❌ | ❌ | ❌ | ❌ |
| User Management | ✅ | ✅ | ❌ | ❌ | ❌ |
| Product Management | ✅ | ✅ | ❌ | ✅ | ❌ |
| Inventory Management | ✅ | ✅ | ❌ | ✅ | ❌ |
| Inventory Transfers | ✅ | ✅ | ❌ | ✅ | ❌ |
| Supplier Management | ✅ | ✅ | ✅ | ❌ | ❌ |
| Purchase Management | ✅ | ✅ | ✅ | ❌ | ❌ |
| Customer Management | ✅ | ✅ | ✅ | ❌ | ✅ |
| Sales Management | ✅ | ✅ | ✅ | ❌ | ✅ |
| Debt Management | ✅ | ✅ | ✅ | ❌ | ❌ |
| Expense Management | ✅ | ✅ | ✅ | ❌ | ❌ |
| Shipping & Logistics | ✅ | ✅ | ✅ | ✅ | ✅ |
| Financial Reports | ✅ | ✅ | ✅ | ❌ | ❌ |
| Consolidated Reports | ✅ | ❌ | ❌ | ❌ | ❌ |
| Branch Comparison | ✅ | ❌ | ❌ | ❌ | ❌ |
| Data Analysis | ✅ | ✅ | ❌ | ❌ | ❌ |
| System Settings | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## 5. Module 1: Dashboard

### 5.1 Overview
The main interface displays key performance indicators (KPIs) in real-time for an at-a-glance view of business health. Users can view KPIs for their assigned branch or consolidated view (HQ users).

### 5.2 Dashboard KPIs

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

### 5.3 Branch Context

| Feature | Description |
|---------|-------------|
| **Branch Selector** | Dropdown to switch between branches |
| **Branch Filter** | Filter all dashboard data by branch |
| **Consolidated View** | View all branches combined (HQ users) |
| **Branch Comparison** | Compare KPIs across branches |

### 5.4 Display Options
- Toggle between **SDG** and **USD** views
- Date range filtering (Today, This Week, This Month, This Year, Custom)
- Chart visualizations (bar, line, pie charts)
- Export options (PDF, Excel, CSV)
- Branch filtering (single branch, multiple branches, consolidated)

---

## 6. Module 2: Product Management

### 6.1 Overview
This module organizes all product data and provides a clear view of inventory specifications with full flexibility for Sudan's dynamic market. Products are shared across all branches.

### 6.2 Core Features

**6.2.1 Product Creation and Modification**
- Add new products with full details
- Edit existing product information
- Support for multiple entry methods:
  - **Bulk Entry:** Enter total cost for entire batch (e.g., "5000 Sport Shoes, Brand: Nike, Cost: 100,000 SDG total")
  - **Individual Entry:** Enter each product separately with its own cost
  - **Mixed Entry:** Handle batches with mixed brands and sizes

**6.2.2 Variant Management**
- Size management (EU, US, UK sizes)
- Color management
- Brand management
- Category and sub-category management

**6.2.3 Product Categorization**
- Categories (e.g., Sport, Casual, Formal, Slippers)
- Sub-categories (e.g., Running, Training, Walking)
- Brands (e.g., Nike, Adidas, Puma, Local brands)

**6.2.4 Cost & Price Tracking**
- **Historical Cost Tracking:** Every price change creates a new record
  - Example: Price in Month 1: 500 SDG, Month 2: 600 SDG, Month 3: 700 SDG
  - All prices are stored historically and linked to specific batches
- **Multi-Currency Support:** Enter prices in both SDG and USD
- **Selling Price:** Strict price set by Admin, cannot be changed by Sales Staff
- **Branch Pricing (Optional):** Different prices per branch

**6.2.5 Stock Tracking**
- Real-time quantity tracking per branch
- Batch tracking (link sales to specific batches)
- Reorder point alerts per branch

---

## 7. Module 3: Inventory Management

### 7.1 Overview
Designed to provide accurate, real-time control over stock levels with support for multiple branches and storage locations.

### 7.2 Core Features

**7.2.1 Stock Reception**
- Add new quantities to inventory per branch
- Support for both individual and bulk entry
- Enter cost in both SDG and USD
- Assign products to specific batches
- Assign stock to specific branch

**7.2.2 Stock Level Monitoring**
- Real-time stock visibility per branch
- View stock by product, brand, category, location, branch
- Stock valuation in SDG and USD per branch

**7.2.3 Branch Stock Transfers**
- Transfer stock between branches
- Transfer request and approval workflow
- Transfer tracking (pending, in-transit, received, cancelled)
- Transfer reports and history

**7.2.4 Physical Inventory Count**
- Schedule and perform physical counts per branch
- Compare system stock vs. physical count
- Handle discrepancies with adjustment documents

**7.2.5 Shortage and Surplus Analysis**
- Identify products below reorder point per branch
- Identify excess stock per branch
- Generate restock recommendations

**7.2.6 Obsolete/Slow-Moving Stock**
- Identify products with low turnover per branch
- Generate reports on slow-moving items
- Suggest discount strategies

**7.2.7 Automated Reorder Alerts**
- Set minimum stock levels per branch
- Automatic notifications when stock is low
- Generate purchase recommendations

**7.2.8 Multiple Storage Locations**
- Support for multiple warehouses/showrooms per branch
- Transfer products between locations within same branch
- Track stock by location

### 7.3 Branch Transfer Workflow

| Step | Action | Description |
|------|--------|-------------|
| 1 | **Request** | Branch A requests stock from Branch B |
| 2 | **Approval** | Branch B approves the request |
| 3 | **Dispatch** | Branch B dispatches stock |
| 4 | **Tracking** | Stock is in transit |
| 5 | **Receiving** | Branch A receives and confirms |
| 6 | **Complete** | Transfer is completed |

---

## 8. Module 4: Supplier Management

### 8.1 Overview
A comprehensive database for managing all supplier relationships and related transactions. Suppliers are shared across branches.

### 8.2 Core Features

**8.2.1 Supplier Database**
- Store supplier details:
  - Name, Phone, Email, Address
  - Contact person
  - Payment terms
  - Rating/performance
  - Branch assignment (optional)

**8.2.2 Purchase Order Management**
- Create and track purchase orders per branch
- Link to specific batches
- Track expected delivery dates

**8.2.3 Payment Tracking**
- Track payments to suppliers per branch
- Record payment method (cash, bank transfer, check)
- Link payments to specific invoices

**8.2.4 Outstanding Payables**
- Monitor supplier debt per branch
- Aging reports for supplier payables
- Payment reminders

**8.2.5 Supplier Account Statements**
- Complete transaction history per branch
- Balance summary
- Export statements

**8.2.6 Purchase Reports**
- By supplier
- By date range
- By product category
- By branch

---

## 9. Module 5: Purchase Management

### 9.1 Overview
A dedicated module for managing all purchase orders and procurement activities per branch.

### 9.2 Core Features

**9.2.1 Purchase Invoice Registration**
- Record supplier invoices per branch
- Enter products and quantities
- Record costs in SDG and USD

**9.2.2 Batch Assignment**
- Assign purchased items to specific batches
- Track batch numbers for accurate COGS
- Batches are branch-specific

**9.2.3 Payment Status Tracking**
- Track paid vs. unpaid amounts per branch
- Record payment history
- Flag overdue payments

**9.2.4 Complete Purchase History**
- Full audit trail of all purchases per branch
- Search and filter by date, supplier, product, branch

**9.2.5 Purchase Reports**
- Customizable by date range
- By supplier
- By product category
- By payment status
- By branch
- Consolidated (HQ view)

---

## 10. Module 6: Customer Management

### 10.1 Overview
A centralized database for managing all customer accounts and sales history. Customers are shared across branches but can have branch-specific relationships.

### 10.2 Core Features

**10.2.1 Customer Database**
- Full customer accounts with complete details
- **Quick Customer Feature:**
  - Required: Name, Phone Number
  - Optional: Email, WhatsApp Number
  - Automatically creates minimal customer record
  - Links debts to specific name for follow-up
- Customers can have a **preferred/default branch**

**10.2.2 Transaction History**
- Complete purchase history per branch
- Payment history per branch
- Debt history per branch

**10.2.3 Total Purchase Calculation**
- Lifetime value
- By date range
- By product category
- By branch

**10.2.4 Customer Account Statements**
- Detailed statement with all transactions per branch
- Consolidated statement (all branches)
- Balance summary
- Export options

**10.2.5 Outstanding Receivables Monitoring**
- Real-time debt visibility per branch
- Consolidated debt view (HQ)
- Aging reports per branch
- Collection prioritization

**10.2.6 Customer Badges**
- Automatic badge assignment based on purchase volume
- Manual badge assignment by Admin
- Badges can influence pricing

**10.2.7 Branch-Customer Relationship**
- Customer can buy from any branch
- Customer can have preferred branch
- Debt tracked per branch or consolidated (configurable)

---

## 11. Module 7: Sales Management

### 11.1 Overview
This module facilitates quick and accurate processing of daily sales transactions per branch.

### 11.2 Core Features

**11.2.1 Sales Invoice Generation**
- Create new sales invoices per branch
- Select customer (Full account or Quick Customer)
- Add products with batch selection
- Branch automatically assigned

**11.2.2 Batch Selection During Sale**
- Show available batches with costs per branch
- Allow selection of specific batch
- Automatically deduct from selected batch

**11.2.3 Product Selection**
- Search by name, brand, category, barcode
- Add products with quantity
- Support for carton/package sales
- View stock availability per branch

**11.2.4 Discount Application**
- **Three types of discounts:**
  1. **Percentage Discount** (e.g., 10%)
  2. **Fixed Amount Discount** (e.g., 500 SDG)
  3. **Manual Price Override** (Admin only)
- Seller can apply discounts within permissions
- Branch-specific discount policies (optional)

**11.2.5 Payment Tracking**
- Record amount paid per branch
- Track outstanding balance
- Support for cash and bank transfers

**11.2.6 Invoice Printing & Sharing**
- Print invoices with branch branding
- Share via WhatsApp, Email, etc.
- PDF generation

**11.2.7 Strict Pricing Policy**
- Seller CANNOT change base price
- Only Admin can change selling price
- Seller can only apply discounts within defined limits

---

## 12. Module 8: Accounts Receivable & Collections

### 12.1 Overview
A critical module for monitoring and managing all outstanding customer debts per branch and consolidated.

### 12.2 Core Features

**12.2.1 Customer Account Statements**
- Complete transaction history per branch
- Consolidated statement (all branches)
- Balance summary
- Filter by date range

**12.2.2 Outstanding Balance Monitoring**
- Real-time view of all debts per branch
- Consolidated view (HQ)
- Group by customer
- Sort by amount or age

**12.2.3 Payment Receipt Registration**
- Record customer payments per branch
- Link payments to specific invoices
- Support for multiple payment methods
  - Cash
  - Bank transfer (with transaction number)
  - Check (with check number)
  - Mobile money (MBok, Bankak - with transaction number)

**12.2.4 Due Date Management**
- **Two options available:**
  1. **Invoice Date + 30 days** (automatic)
  2. **Specific Due Date** (user-defined)
- Automatic flagging as "Overdue" after due date
- Per branch or consolidated

**12.2.5 Debt Aging Reports**

| Aging Category | Description | Branch Scope |
| :--- | :--- | :--- |
| **Current (0-30 days)** | Not yet due | Per Branch / Consolidated |
| **Overdue (30-60 days)** | 30-60 days past due | Per Branch / Consolidated |
| **Overdue (60-90 days)** | 60-90 days past due | Per Branch / Consolidated |
| **Overdue (90+ days)** | More than 90 days past due | Per Branch / Consolidated |

**12.2.6 Collection Notifications**
- Automated reminders for upcoming due dates
- Alerts for overdue accounts
- Dashboard notifications for collections team
- Per branch or consolidated

**12.2.7 Collections Performance Reports**
- Collection rate by customer
- Collection rate by period
- Collection rate by branch
- Effectiveness tracking

---

## 13. Module 9: Expense Management

### 13.1 Overview
Provides complete control and oversight over all business operating expenses per branch.

### 13.2 Core Features

**13.2.1 Daily Expense Recording**
- Record all business expenses per branch
- Categorize expenses
- Enter amount in SDG (and optionally USD)

**13.2.2 Expense Categories**
- **Fixed Expenses:**
  - Rent (per branch)
  - Salaries & Wages (per branch)
  - Utilities (Electricity, Water) (per branch)
  - Internet & Telecommunications (per branch)
- **Variable Expenses:**
  - Transport & Logistics (per branch)
  - Maintenance (per branch)
  - Marketing (per branch)
  - Miscellaneous (per branch)

**13.2.3 Expense Linking**
- **Both options available:**
  1. **Link to Specific Action/Order:** Directly link an expense to a specific purchase order or shipment
  2. **Global Expense:** Track separately and deduct globally

**13.2.4 Expense Reports**
- By category
- By date range
- By linked action (if applicable)
- By branch
- Consolidated (HQ view)
- Comparative analysis

**13.2.5 Profit Deduction**
- Expenses are automatically deducted from revenue
- Net profit calculation after all expenses
- Both options for deduction timing:
  1. **Per-Transaction:** Deduct immediately
  2. **Periodic (Global):** Deduct at end of period
- Per branch or consolidated

---

## 14. Module 10: Shipping & Logistics

### 14.1 Overview
Enables the management and tracking of goods shipped to customers, both locally and out-of-state, until delivery confirmation per branch.

### 14.2 Core Features

**14.2.1 Shipping Request Creation**
When making a sale, the system creates a shipping request with:
- Customer Name
- Contact Number
- Dispatch City (branch location)
- Delivery City
- Logistics Company
- Number of Boxes/Cartons
- Shipment Value
- Shipping Cost
- Additional Notes
- Shipment Status Tracking
- Branch assignment

**14.2.2 Shipment Statuses**
- Pending Processing
- Handed to Logistics Company
- In Transit
- Arrived at Destination City
- Delivered / Received

**14.2.3 Logistics Companies Management**
- Company Data Management
- Contact Information
- Shipping History
- Service Rating
- Shipping Cost Tracking
- Branch assignment (optional)

**14.2.4 Shipping Cost Tracking**
- Track costs in SDG and USD
- **Two deduction methods (both supported):**
  1. **Per-Invoice:** Deducted from specific sale invoice profit
  2. **Global:** Collected and deducted globally
- Admin sets default method in system settings
- Per branch or consolidated

**14.2.5 Return & Adjustment Process**
- **Option B (Approved):** Create "Return from Shipment" document
- Add missing items back to inventory
- Create full audit trail
- Handle partial returns

**14.2.6 Proof of Delivery Management**
- Attach PDF/image of proof of delivery (POD)
- Store waybill numbers
- Support for electronic delivery receipts

**14.2.7 Shipping Reports**
- Completed Shipments
- Pending Shipments
- Returned Shipments
- Shipping Cost Analysis
- Most Requested Cities
- Most Used Logistics Companies
- Per branch
- Consolidated (HQ view)

### 14.3 Workflow Example

**Scenario:** A customer in **Madani** orders **50 boxes** of shoes from **Atbara Branch**.

1. **Staff Action:** Creates a shipping record
   - **Customer:** Ahmed Mohamed
   - **Dispatch City:** Atbara
   - **Delivery City:** Madani
   - **Logistics Company:** XYZ
   - **Boxes:** 50
   - **Branch:** Atbara Branch
2. **System Tracking:** Initial status set to `Handed to Logistics Company`
3. **Update:** Upon delivery, status updated to `Delivered / Received`
4. **Impact:** Inventory updated at Atbara Branch, shipping cost recorded, profit adjusted

---

## 15. Module 11: Financial Management & Accounting

### 15.1 Overview
This is the central hub for financial control, providing management with accurate data on revenue, expenses, profits, and debts for informed decision-making per branch and consolidated.

### 15.2 Core Features

**15.2.1 Treasury and Cash Management**
- Current Cash Balance per branch
- Cash Receipts per branch
- Cash Payments per branch
- Daily Cash Flow per branch
- Daily Financial Closing per branch
- Revenue Tracking per branch

**15.2.2 Revenue Tracking**
Tracks all income from sales and other sources:
- Daily Revenue per branch
- Monthly Revenue per branch
- Annual Revenue per branch
- Consolidated revenue (HQ view)
- In both SDG and USD

**15.2.3 Expense Management**
- Rent (per branch)
- Salaries & Wages (per branch)
- Utilities (per branch)
- Internet & Telecommunications (per branch)
- Transport & Logistics (per branch)
- Maintenance (per branch)
- Miscellaneous Expenses (per branch)

**15.2.4 Accounts Receivable (Customer Debt)**
- Customer Balances per branch
- Consolidated customer balances (HQ view)
- Due Installments per branch
- Payment Records per branch
- Outstanding Balances per Customer per branch
- Due Date Alerts per branch

**15.2.5 Accounts Payable (Supplier Debt)**
- Total Purchases per branch
- Amount Paid per branch
- Outstanding Balance per branch
- Payment Due Dates per branch

**15.2.6 Multi-Currency Support**
- All financial data tracked in both SDG and USD
- Historical exchange rate tracking
- Dollar rate set manually by Admin
- Rate can change at any time
- System stores exchange rate at time of transaction
- Optional support for additional currencies

**15.2.7 Profit & Loss Analysis**
Provides a clear view of financial performance over any period:
- Total Sales (per branch and consolidated)
- Cost of Goods Sold (COGS) (per branch and consolidated)
- Total Expenses (per branch and consolidated)
- Net Profit (per branch and consolidated)
- Net Loss (per branch and consolidated)
- Collections (per branch and consolidated)
- In both SDG and USD

**15.2.8 Collections Management**
- Collection Registration per branch
- Amount Collected Tracking per branch
- Outstanding Customer Balances per branch
- Collection Account Statements per branch

### 15.3 Financial Reports

| # | Report Name | Description | Branch Scope |
|---|-------------|-------------|--------------|
| 1 | **General Financial Report** | Complete financial position summary | Per Branch / Consolidated |
| 2 | **Profit & Loss Statement** | Net profit calculation for any period | Per Branch / Consolidated |
| 3 | **Revenue Report** | Revenue analysis by time period | Per Branch / Consolidated |
| 4 | **Expense Report** | Expense breakdown by type and period | Per Branch / Consolidated |
| 5 | **Debt Report** | All outstanding customer receivables | Per Branch / Consolidated |
| 6 | **Supplier Report** | All outstanding supplier payables | Per Branch / Consolidated |
| 7 | **Cash Flow Report** | Daily financial transaction history | Per Branch / Consolidated |
| 8 | **Periodic Comparison Report** | Side-by-side comparison across periods | Per Branch / Consolidated |
| 9 | **Branch Performance Report** | Compare all branches side-by-side | Consolidated |
| 10 | **Branch Comparison Report** | Side-by-side comparison across branches | Consolidated |

### 15.4 Cost of Goods Sold (COGS) Calculation

**Method: Batch Tracking with Historical Costs**

The system tracks costs by batch. Each batch has:
- **Purchase Date**
- **Quantity**
- **Cost per Unit** (in SDG and USD)
- **Branch Assignment**

**When a sale is made:**
1. User selects the specific batch to sell from
2. System deducts from that batch's quantity
3. COGS is calculated using that batch's cost
4. Profit margin is calculated based on that specific cost
5. Profit is attributed to the branch

**Example:**
- **Batch 1 (3 months ago):** 500 pairs at 500 SDG each (Branch A)
- **Batch 2 (2 months ago):** 300 pairs at 600 SDG each (Branch A)
- **Batch 3 (Now):** 200 pairs at 700 SDG each (Branch B)

When selling 100 pairs from Branch A:
- User selects Batch 1
- COGS = 100 × 500 SDG = 50,000 SDG
- Profit = Sale Price - 50,000 SDG
- Profit attributed to Branch A

---

## 16. Module 12: Data Analysis & Prediction

### 16.1 Overview
This advanced module analyzes historical data to provide predictive insights and recommendations for better business decisions per branch and consolidated.

### 16.2 Core Features

**16.2.1 Sales Prediction**
- Analyze historical sales patterns per branch
- Predict future sales based on trends
- Seasonal pattern identification per branch
- Growth trend analysis per branch
- Consolidated prediction (HQ view)

**16.2.2 Product Performance Analysis**
- Identify best-selling products per branch
- Identify slow-moving products per branch
- Recommend discount strategies for slow movers
- Predict product lifecycle per branch
- Consolidated analysis (HQ view)

**16.2.3 Brand/Category Analysis**
- Best-selling brands per branch
- Best-performing categories per branch
- Brand profitability analysis per branch
- Category growth trends per branch
- Consolidated analysis (HQ view)

**16.2.4 Customer Analysis**
- Customer lifetime value prediction per branch
- Customer churn prediction per branch
- Segmentation analysis per branch
- Repeat purchase patterns per branch
- Consolidated analysis (HQ view)

**16.2.5 Inventory Optimization**
- Recommend optimal stock levels per branch
- Predict stockout dates per branch
- Suggest reorder quantities per branch
- Reduce overstock situations per branch

**16.2.6 Revenue & Profit Forecasting**
- Future revenue projections per branch
- Profit margin predictions per branch
- Expense forecasting per branch
- Cash flow projections per branch
- Consolidated forecasting (HQ view)

**16.2.7 Branch Performance Analysis**
- Compare performance across branches
- Identify best-performing branches
- Identify underperforming branches
- Recommend improvements for underperforming branches

**16.2.8 Business Intelligence Dashboards**
- Interactive visualizations
- Drill-down capabilities
- Custom report builder
- Export insights to PDF/Excel
- Branch filtering

### 16.3 Data Sources
- Sales history per branch
- Purchase history per branch
- Expense records per branch
- Inventory data per branch
- Customer data (shared)
- Supplier data (shared)
- Shipping data per branch
- Branch data
- External market data (optional)

### 16.4 Reporting Outputs
- Visual charts and graphs
- Tabular reports
- PDF exports
- Excel exports
- Email notifications
- Dashboard widgets
- Branch filtering

---

## 17. Technical Specifications

### 17.1 Technology Stack Recommendations

| Component | Technology |
|-----------|------------|
| **Frontend** | Livewire 3 + Alpine.js with Tailwind CSS |
| **Backend** | Laravel 11.x (PHP 8.2+) |
| **Database** | MySQL 8.0+ (with JSON support) |
| **Cache/Queue** | Redis 7.0+ |
| **Mobile App** | PWA (Progressive Web App) |
| **Reporting** | Chart.js / ApexCharts |
| **Analytics** | Custom ML models |
| **Authentication** | Laravel Sanctum |
| **File Storage** | Local / Cloud (AWS S3, etc.) |
| **Web Server** | Apache (cPanel compatible) |

### 17.2 System Requirements

**Server Requirements:**
- Linux / Windows Server
- 8GB RAM minimum
- 4 CPU cores minimum
- 100GB Storage minimum (expandable)
- SSL Certificate (HTTPS)
- PHP 8.2+ with required extensions
- MySQL 8.0+
- Redis 7.0+

**Client Requirements:**
- Modern web browser (Chrome, Firefox, Edge)
- Internet connection (or offline mode with sync)
- Optional: Barcode/QR scanner

### 17.3 Security Features

- **Role-Based Access Control (RBAC)**
- **Audit Trail:** All critical actions logged
- **Data Encryption:** At rest and in transit
- **Backup:** Automated daily backups
- **SSL/TLS:** Secure communication
- **Two-Factor Authentication:** Optional
- **Branch Isolation:** Users only see their branch data

### 17.4 Offline Capability
- Offline-first architecture
- Local storage (IndexedDB)
- Sync when connection restored
- Conflict resolution strategies

### 17.5 Scalability
- Modular architecture
- Microservices ready
- Horizontal scaling
- Load balancing
- Database sharding (if needed)

---

## 18. System Benefits

### 18.1 Operational Benefits

| # | Benefit | Description |
|---|---------|-------------|
| 1 | **Centralized Operations** | All branches managed from one platform |
| 2 | **Error Reduction** | Minimizes manual data entry errors |
| 3 | **Accurate Inventory** | Real-time stock visibility per branch |
| 4 | **Enhanced Management** | Improved oversight of sales and procurement across branches |
| 5 | **Efficient Collections** | Streamlined debt management per branch |
| 6 | **Real-Time P&L** | Immediate insight into profitability per branch |
| 7 | **Data-Driven Decisions** | Detailed reports for strategic planning |
| 8 | **Increased Efficiency** | Improved workflow and staff productivity |
| 9 | **Customer Retention** | Better customer service across branches |
| 10 | **Supplier Management** | Improved supplier relationships and procurement |
| 11 | **Branch Autonomy** | Each branch operates independently |
| 12 | **HQ Oversight** | Complete visibility of all branches |

### 18.2 Financial Benefits

| # | Benefit | Description |
|---|---------|-------------|
| 1 | **Better Profit Margins** | Accurate COGS tracking per branch |
| 2 | **Reduced Waste** | Optimized inventory levels per branch |
| 3 | **Improved Cash Flow** | Better debt management per branch |
| 4 | **Cost Control** | Detailed expense tracking per branch |
| 5 | **Revenue Growth** | Data-driven sales strategies per branch |
| 6 | **Risk Reduction** | Predictive analytics per branch |
| 7 | **Branch Profitability** | Identify which branches are profitable |

### 18.3 Strategic Benefits

| # | Benefit | Description |
|---|---------|-------------|
| 1 | **Market Adaptation** | Flexibility for Sudan's unique market |
| 2 | **Scalability** | Easily add new branches |
| 3 | **Competitive Advantage** | Advanced analytics and predictions |
| 4 | **Decision Support** | Data-driven strategic planning |
| 5 | **Future-Proof** | Multi-currency, multi-location support |
| 6 | **Branch Performance** | Compare and improve branch performance |
| 7 | **Consolidated Reporting** | Complete view of all branches |

---

## Appendix

### A. Glossary of Terms

| Term | Definition |
|------|------------|
| **ERP** | Enterprise Resource Planning |
| **COGS** | Cost of Goods Sold |
| **SKU** | Stock Keeping Unit |
| **FIFO** | First-In, First-Out |
| **SDG** | Sudanese Pound |
| **USD** | United States Dollar |
| **POD** | Proof of Delivery |
| **KPI** | Key Performance Indicator |
| **RBAC** | Role-Based Access Control |
| **PWA** | Progressive Web Application |
| **Batch** | A group of products purchased together with a specific cost |
| **Quick Customer** | Minimal customer record (Name + Phone) for unregistered buyers |
| **Aging Report** | Report showing debts categorized by how long they've been outstanding |
| **Branch** | An independent business unit with its own inventory, users, and reports |
| **Stock Transfer** | Movement of stock from one branch to another |
| **Consolidated Report** | Report showing data from all branches combined |

### B. Acronyms

| Acronym | Full Form |
|---------|-----------|
| SRS | System Requirements Specification |
| UI | User Interface |
| UX | User Experience |
| API | Application Programming Interface |
| JSON | JavaScript Object Notation |
| JWT | JSON Web Token |
| SSL | Secure Sockets Layer |
| TLS | Transport Layer Security |
| ML | Machine Learning |
| AI | Artificial Intelligence |
| HQ | Headquarters |

---

## Document Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Client Representative | | | |
| System Designer | | | |
| Project Manager | | | |

---

**© 2026 - All Rights Reserved**

*This document is confidential and proprietary. It contains information intended only for the specified recipients. Unauthorized use, disclosure, or distribution is prohibited.*

---