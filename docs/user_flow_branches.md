# Wholesale Shoe Store ERP System
## User Flows & Role-Based Abilities (With Multi-Branch Support)

**Document Type:** User Journey & Role Flow  
**Version:** 2.0  
**Date:** June 2026  
**Purpose:** This document outlines complete user flows for each role in the ERP system with full multi-branch support, detailing their abilities, actions, and navigation paths.

---

## Table of Contents

1. [Multi-Branch Context Overview](#1-multi-branch-context-overview)
2. [Super Admin User Flow](#2-super-admin-user-flow)
3. [Admin/Branch Manager User Flow](#3-adminbranch-manager-user-flow)
4. [Area Manager User Flow](#4-area-manager-user-flow)
5. [HQ Staff User Flow](#5-hq-staff-user-flow)
6. [Accountant User Flow](#6-accountant-user-flow)
7. [Warehouse Keeper User Flow](#7-warehouse-keeper-user-flow)
8. [Sales Staff User Flow](#8-sales-staff-user-flow)
9. [Common Flows Across All Roles](#9-common-flows-across-all-roles)
10. [Cross-Role & Cross-Branch Interaction Flows](#10-cross-role--cross-branch-interaction-flows)

---

# 1. Multi-Branch Context Overview

## 1.1 Branch Concept

Each branch functions as an independent business unit with its own:
- Inventory and stock levels
- Users and staff assignments
- Sales and purchases
- Expenses and shipping
- Debt management
- Financial reports

### Data Sharing Across Branches

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

---

## 1.2 Branch Context in UI

### Branch Selector
Every user sees a **branch selector** in the header:

```
[Logo] [Branch Selector ▼] [Search] [Notifications] [Profile]
```

**Branch Selector Dropdown:**
```
- Branch A (Current)
- Branch B
- Branch C
- All Branches (HQ/Area Manager view)
```

### User Views by Branch Assignment

| User Type | Default View | Available Views |
|-----------|--------------|-----------------|
| **Branch User** | Their branch only | Single branch only |
| **Area Manager** | First branch or all | Multiple branches / Consolidated |
| **HQ Staff** | All branches | All branches / Consolidated |
| **Super Admin** | All tenants/branches | All tenants and branches |

---

## 1.3 Branch-Specific User Roles

| Role | Description | Branch Scope |
|------|-------------|--------------|
| **Super Admin** | Full system access across all tenants and branches | All Tenants, All Branches |
| **Admin (Branch Manager)** | Full access for their branch only | Own Branch Only |
| **Area Manager** | Oversee multiple branches | Multiple Branches |
| **HQ Staff** | View all branches, consolidated reports | All Branches |
| **Accountant** | Financial management for assigned branch(es) | Assigned Branch(es) |
| **Warehouse Keeper** | Inventory management for assigned branch(es) | Assigned Branch(es) |
| **Sales Staff** | Sales processing for assigned branch(es) | Assigned Branch(es) |

---

# 2. Super Admin User Flow

## 2.1 Role Overview

**Role Name:** Super Admin  
**Access Level:** Full System Access  
**Primary Responsibility:** System management, tenant management, branch management (across tenants), system settings  
**Accessible Modules:** All modules (system-wide, across all tenants and branches)

---

## 2.2 Super Admin Dashboard

### Login & Authentication
1. Navigate to system login page
2. Enter Super Admin credentials
3. Authenticate with 2FA (if enabled)
4. Redirected to Super Admin Dashboard

### Dashboard View
The Super Admin dashboard displays:

- **System Overview**
  - Total number of tenants
  - Total number of branches across all tenants
  - Total number of users across all tenants
  - Active vs. suspended tenants and branches
  - System health status (CPU, Memory, Disk usage)

- **Tenant & Branch Metrics**
  - Recent tenant activities
  - Tenants by subscription plan
  - Branches per tenant
  - New tenants/branches this month
  - Tenant status distribution (active, suspended, trial)
  - Branch status distribution (active, suspended)

- **System Alerts**
  - Failed backups
  - Server errors
  - Low disk space
  - Failed cron jobs

- **Recent Activity Log**
  - User logins across all tenants and branches
  - System changes
  - Permission changes
  - Branch changes

---

## 2.3 Super Admin Flows

### Tenant Management

#### Flow 1: Create New Tenant

**Trigger:** New business subscribes to the system

**Steps:**
1. Click "Tenant Management" from dashboard
2. Click "Add New Tenant" button
3. Fill in tenant details:
   - Business Name
   - Domain/Subdomain
   - Contact Information
   - Subscription Plan
   - Database Name (auto-generated)
4. Configure tenant settings:
   - Default Language (Arabic/English)
   - Default Currency
   - Timezone
5. Select which modules to enable
6. Set tenant logo and branding colors
7. **Create initial branches (optional):**
   - Branch Name
   - Branch Address
   - Branch Phone
   - Branch Manager assignment
8. Click "Create Tenant"
9. System creates:
   - New database
   - Runs migrations
   - Seeds default data
   - Creates default admin user
   - Creates initial branch(es)
   - Sends welcome email to tenant admin
10. Redirect to tenant overview

**Success Criteria:**
- Tenant is created with isolated data
- Initial branches created
- Tenant admin receives login credentials
- Tenant is listed in tenant management grid
- Tenant appears in system health monitoring

---

#### Flow 2: Manage Tenant Branches

**Trigger:** Tenant needs to add/modify branches

**Steps:**
1. Navigate to Tenant Management
2. Select tenant
3. Click "Branches" tab
4. View all branches for this tenant
5. **Add new branch:**
   - Click "Add Branch"
   - Fill in branch details (name, address, phone, email)
   - Assign branch manager
   - Set branch settings (currency, timezone, invoice prefix)
   - Click "Save"
6. **Edit branch:**
   - Click branch name
   - Update branch details
   - Update branch settings
   - Click "Update"
7. **Suspend branch:**
   - Click "Suspend" button
   - Confirm suspension
   - Branch status updates to "Suspended"
   - All branch users logged out
8. **Activate branch:**
   - Click "Activate" button
   - Branch status updates to "Active"

**Success Criteria:**
- Branches managed correctly
- Branch status updated
- All changes reflected immediately

---

#### Flow 3: Suspend/Deactivate Tenant

**Trigger:** Tenant violates terms or payment failure

**Steps:**
1. Navigate to Tenant Management
2. Search/find tenant
3. Click "Suspend" button
4. Confirm suspension
5. System:
   - Deactivates tenant
   - Deactivates all branches under tenant
   - Prevents login for all tenant users
   - Shows suspension reason
6. Tenant status updates to "Suspended"
7. Notification sent to tenant admin

**Success Criteria:**
- Tenant is suspended immediately
- All branches suspended
- All tenant users are logged out
- System shows suspension status

---

#### Flow 4: Manage System Settings

**Trigger:** System-wide changes needed

**Steps:**
1. Click "System Settings" from dashboard
2. Configure general settings:
   - System Name
   - System URL
   - Default Language
   - Default Timezone
   - Date Format
3. Configure email settings:
   - SMTP Configuration
   - From Email
   - From Name
4. Configure backup settings:
   - Backup Schedule
   - Backup Location
   - Backup Retention
5. Configure security settings:
   - Password Policy
   - Session Timeout
   - 2FA Requirements
6. Click "Save Settings"
7. System applies changes
8. Confirmation message displayed

**Success Criteria:**
- Settings applied system-wide
- All tenants and branches inherit defaults (unless overridden)
- Changes reflected immediately

---

# 3. Admin/Branch Manager User Flow

## 3.1 Role Overview

**Role Name:** Admin / Branch Manager  
**Access Level:** Full tenant access for their branch only  
**Primary Responsibility:** Business operations management for their branch, user management (branch-level), financial oversight for their branch  
**Accessible Modules:** All modules within their branch (except Super Admin features)

---

## 3.2 Branch Manager Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter Admin/Branch Manager credentials
3. Authenticate
4. Redirected to Branch Manager Dashboard (their branch only)

### Dashboard View
The Branch Manager dashboard displays:

- **Key Performance Indicators (KPIs)** - For their branch only
  - Total Revenue (Today/This Month/This Year)
  - Total Expenses (Today/This Month/This Year)
  - Net Profit (Today/This Month/This Year)
  - Total Outstanding Debt
  - Total Products in Stock
  - Low Stock Items Count
  - Overdue Debt Amount

- **Sales Metrics** - For their branch only
  - Sales by Date (Today/This Week/This Month)
  - Top Selling Products
  - Top Selling Brands
  - Top Customers

- **Financial Overview** - For their branch only
  - Revenue vs. Expenses
  - Profit/Loss Chart
  - Debt Aging Summary
  - Cash Flow Summary

- **Operational Alerts** - For their branch only
  - Low Stock Alerts
  - Overdue Debt Alerts
  - Pending Shipments
  - Pending Purchase Orders
  - Stock Transfer Requests (incoming/outgoing)

---

## 3.3 Branch Manager Flows

### Branch User Management

#### Flow 5: Create Branch User

**Trigger:** Hiring new staff for the branch

**Steps:**
1. Click "User Management" from sidebar
2. Click "Add User" button
3. Fill in user details:
   - Full Name
   - Email Address
   - Phone Number
   - Role (Accountant, Warehouse Keeper, Sales Staff)
   - Username
   - Password
4. **Branch assignment is automatic** (user assigned to manager's branch)
5. Click "Create User"
6. System:
   - Creates user account
   - Assigns selected role
   - Assigns user to manager's branch
   - Sends login credentials via email/SMS
7. User appears in user list

**Success Criteria:**
- New user account created
- Correct role permissions assigned
- User assigned to correct branch
- User can log in with credentials
- User appears in staff list

---

#### Flow 6: View Branch Performance

**Trigger:** Need to check branch performance

**Steps:**
1. Click "Reports" from sidebar
2. Select "Branch Performance Report"
3. View branch metrics:
   - Total Revenue
   - Total Expenses
   - Net Profit
   - Total Sales
   - Total Customers
   - Inventory Value
   - Outstanding Debt
4. View charts and trends
5. Compare with previous periods
6. Export report (PDF/Excel)

**Success Criteria:**
- Complete branch performance displayed
- Charts accurate
- Export available

---

#### Flow 7: Request Stock Transfer

**Trigger:** Branch needs stock from another branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Request Stock Transfer"
3. Select product(s)
4. Enter quantity needed
5. Select source branch (from)
6. Add notes (optional)
7. Click "Submit Request"
8. System:
   - Creates transfer request
   - Status = "Pending"
   - Notifies source branch manager
9. Track request status

**Success Criteria:**
- Transfer request created
- Source branch notified
- Request appears in transfer list

---

#### Flow 8: Approve Stock Transfer

**Trigger:** Another branch requests stock from this branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Stock Transfer Requests"
3. View incoming requests
4. Click request to view details:
   - Requesting Branch
   - Products/Quantities
   - Notes
5. Review available stock
6. **Approve or Reject:**
   - If approve: Confirm quantities, set dispatch date
   - If reject: Provide reason
7. Click "Process Request"
8. System:
   - Updates request status
   - Reserves stock (if approved)
   - Notifies requesting branch

**Success Criteria:**
- Request processed
- Stock reserved (if approved)
- Notification sent

---

#### Flow 9: Receive Stock Transfer

**Trigger:** Stock transfer arrives from another branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Stock Transfer Receiving"
3. View incoming transfers
4. Click transfer to receive
5. Verify received items:
   - Check quantities
   - Check quality/condition
6. Confirm receipt
7. Click "Complete Receiving"
8. System:
   - Updates inventory
   - Completes transfer
   - Notifies source branch

**Success Criteria:**
- Stock received
- Inventory updated
- Transfer completed

---

#### Flow 10: Create Purchase Order (Branch-Specific)

**Trigger:** Need to order from supplier for the branch

**Steps:**
1. Click "Purchases" from sidebar
2. Click "Create Purchase Order"
3. Select supplier
4. Add products:
   - Search/add product
   - Enter quantity
   - Enter cost per unit (SDG & USD)
5. **Branch is auto-assigned** (manager's branch)
6. Review order total
7. Add notes (optional)
8. Click "Submit Purchase Order"
9. System:
   - Creates purchase order for the branch
   - Updates purchase status
   - Notifies warehouse (if applicable)

**Success Criteria:**
- Purchase order created for specific branch
- Status set to "Draft" or "Ordered"
- Supplier notified (if enabled)
- Order appears in purchase list

---

#### Flow 11: Receive Purchase Order (Branch-Specific)

**Trigger:** Products arrive from supplier at the branch

**Steps:**
1. Navigate to Purchases
2. Find purchase order
3. Click "Receive Order"
4. Verify products received:
   - Confirm quantities received
   - Check quality/condition
   - Note any discrepancies
5. Enter receiving details:
   - Receive quantity per product
   - Batch number (auto-generated)
   - Branch is auto-assigned
   - Storage location (within branch)
6. Click "Complete Receiving"
7. System:
   - Creates product batches for the branch
   - Updates inventory quantities for the branch
   - Updates purchase order status
   - Creates batch costs

**Success Criteria:**
- Products added to branch inventory
- Batches created with cost tracking
- Purchase order status updated
- Branch inventory quantities increased

---

#### Flow 12: Configure Branch Settings

**Trigger:** Need to customize branch settings

**Steps:**
1. Click "Settings" from sidebar
2. Configure branch settings:
   - Branch Name
   - Branch Address, Phone, Email
   - Branch Logo
   - Branch Colors (primary/secondary)
   - Invoice Prefix (branch-specific)
   - Default Currency (overrides tenant default)
   - Default Language (overrides tenant default)
   - Timezone (overrides tenant default)
3. Configure notification settings for branch
4. Click "Save Settings"
5. System applies changes

**Success Criteria:**
- Settings applied to branch only
- Branding updated for branch
- All modules reflect branch settings

---

# 4. Area Manager User Flow

## 4.1 Role Overview

**Role Name:** Area Manager  
**Access Level:** Multiple branches oversight  
**Primary Responsibility:** Oversee multiple branches, performance monitoring, consolidated reporting, cross-branch coordination  
**Accessible Modules:** All modules across assigned branches (multiple branch views, consolidated reports)

---

## 4.2 Area Manager Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter Area Manager credentials
3. Authenticate
4. Redirected to Area Manager Dashboard

### Dashboard View
The Area Manager dashboard displays:

- **Consolidated KPIs (All Assigned Branches)**
  - Total Revenue (Today/This Month/This Year)
  - Total Expenses (Today/This Month/This Year)
  - Total Net Profit (Today/This Month/This Year)
  - Total Outstanding Debt
  - Total Products in Stock (all branches combined)

- **Branch Performance Comparison**
  - Revenue per branch (bar chart)
  - Expenses per branch (bar chart)
  - Profit per branch (bar chart)
  - Sales volume per branch

- **Branch Ranking**
  - Best performing branch (by revenue)
  - Best performing branch (by profit margin)
  - Branch with most sales
  - Branch with lowest expenses

- **Operational Alerts (All Branches)**
  - Low stock alerts per branch
  - Overdue debt alerts per branch
  - Pending shipments per branch
  - Pending stock transfers between branches

---

## 4.3 Area Manager Flows

### Multi-Branch Oversight

#### Flow 13: View Consolidated Reports

**Trigger:** Need to see performance across all branches

**Steps:**
1. Click "Reports" from sidebar
2. Select "Consolidated Reports"
3. Set date range
4. View consolidated metrics:
   - Total Revenue (all branches combined)
   - Total Expenses (all branches combined)
   - Total Net Profit (all branches combined)
   - Total Outstanding Debt
   - Total Inventory Value
5. View charts:
   - Revenue by Branch (pie/bar chart)
   - Expenses by Branch (pie/bar chart)
   - Profit by Branch (pie/bar chart)
6. Export report (PDF/Excel)

**Success Criteria:**
- Consolidated metrics displayed
- Branch breakdown visible
- Export available

---

#### Flow 14: Compare Branch Performance

**Trigger:** Need to identify best/worst performing branches

**Steps:**
1. Click "Reports" from sidebar
2. Select "Branch Comparison Report"
3. Set date range
4. View side-by-side comparison:
   - Revenue per branch
   - Expenses per branch
   - Net Profit per branch
   - Sales volume per branch
   - Customer count per branch
   - Debt per branch
5. View ranking:
   - Best performing branch
   - Worst performing branch
   - Improvement areas
6. Click branch name for detailed report
7. Export report (PDF/Excel)

**Success Criteria:**
- Side-by-side comparison displayed
- Ranking visible
- Drill-down available
- Export available

---

#### Flow 15: Approve Stock Transfer Between Branches

**Trigger:** Branches request stock transfers (needs approval)

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Stock Transfer Approvals"
3. View pending transfer requests
4. Click request to view details:
   - Requesting Branch
   - Source Branch
   - Products/Quantities
   - Reason/Notes
5. Review availability at source branch
6. **Approve or Reject:**
   - If approve: Confirm quantities, set dispatch date
   - If reject: Provide reason
7. Click "Process Request"
8. System:
   - Updates request status
   - Notifies both branches

**Success Criteria:**
- Transfer approved/rejected
- Both branches notified
- Status updated

---

#### Flow 16: View Branch Details

**Trigger:** Need to investigate specific branch performance

**Steps:**
1. Click "Branches" from sidebar
2. View all assigned branches
3. Click branch name
4. View branch details:
   - Branch Information
   - Branch Manager
   - Staff List
   - Performance Metrics
   - Recent Activity
   - Audit Logs
5. Click any metric for detailed report
6. Export branch report

**Success Criteria:**
- Complete branch information displayed
- Performance metrics visible
- Drill-down available

---

# 5. HQ Staff User Flow

## 5.1 Role Overview

**Role Name:** HQ Staff (Headquarters)  
**Access Level:** All branches view, consolidated reporting  
**Primary Responsibility:** Consolidated reporting, strategic planning, overall business oversight  
**Accessible Modules:** All modules (view all branches, consolidated reports, cannot change branch settings)

---

## 5.2 HQ Staff Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter HQ Staff credentials
3. Authenticate
4. Redirected to HQ Dashboard

### Dashboard View
The HQ Staff dashboard displays:

- **Consolidated KPIs (All Branches)**
  - Total Revenue (Today/This Month/This Year)
  - Total Expenses (Today/This Month/This Year)
  - Total Net Profit (Today/This Month/This Year)
  - Total Outstanding Debt
  - Total Products in Stock (all branches combined)

- **Branch Performance Overview**
  - Revenue per branch (bar chart)
  - Expenses per branch (bar chart)
  - Profit per branch (bar chart)
  - Sales volume per branch

- **Trend Analysis**
  - Revenue trend (line chart - all branches)
  - Expense trend (line chart - all branches)
  - Profit trend (line chart - all branches)

- **Strategic Alerts**
  - Branches with declining performance
  - Branches with high debt
  - Branches with low stock
  - Branches with high expenses

---

## 5.3 HQ Staff Flows

### Consolidated Management

#### Flow 17: Generate Consolidated P&L

**Trigger:** Need to assess overall business profitability

**Steps:**
1. Click "Reports" from sidebar
2. Select "Consolidated Profit & Loss"
3. Set date range
4. View consolidated P&L:
   - Total Revenue (all branches)
   - Total COGS (all branches)
   - Gross Profit
   - Total Expenses (all branches)
   - Net Profit/Loss
5. View branch breakdown:
   - Revenue by Branch
   - Expenses by Branch
   - Profit by Branch
6. Compare with previous periods
7. Export report (PDF/Excel)

**Success Criteria:**
- Consolidated P&L displayed
- Branch breakdown visible
- Export available

---

#### Flow 18: View Consolidated Debt Aging

**Trigger:** Need to see debt across all branches

**Steps:**
1. Click "Reports" from sidebar
2. Select "Consolidated Debt Aging Report"
3. View debt categories across all branches:
   - Current (0-30 days) - total + branch breakdown
   - Overdue (30-60 days) - total + branch breakdown
   - Overdue (60-90 days) - total + branch breakdown
   - Overdue (90+ days) - total + branch breakdown
4. Click on branch to view details
5. Export report (PDF/Excel)

**Success Criteria:**
- Consolidated debt aging displayed
- Branch breakdown visible
- Drill-down available
- Export available

---

#### Flow 19: Identify Underperforming Branches

**Trigger:** Need to identify branches needing attention

**Steps:**
1. Click "Analytics" from sidebar
2. Select "Branch Performance Analysis"
3. View branch ranking:
   - Revenue ranking
   - Profit ranking
   - Expense ranking
   - Debt ranking
4. Identify bottom-performing branches
5. Click branch name for detailed analysis
6. Export report (PDF/Excel)

**Success Criteria:**
- Underperforming branches identified
- Detailed analysis available
- Export available

---

# 6. Accountant User Flow

## 6.1 Role Overview

**Role Name:** Accountant  
**Access Level:** Financial Management for assigned branch(es)  
**Primary Responsibility:** Financial recording, reporting, debt management, expense tracking for assigned branch(es)  
**Cannot:** Change product prices, change system settings, manage users  
**Accessible Modules:** Sales (view only), Purchases (view only), Expenses, Debt & Collections, Financial Reports (per branch or consolidated)

---

## 6.2 Accountant Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter Accountant credentials
3. Authenticate
4. Redirected to Accountant Dashboard (branch selector available)

### Dashboard View
The Accountant dashboard displays:

- **Financial KPIs (Selected Branch or All)**
  - Today's Revenue
  - Today's Expenses
  - Today's Net Profit
  - Total Outstanding Debt
  - Overdue Debt Amount
  - Total Payables to Suppliers

- **Revenue Breakdown**
  - Revenue by Payment Method
  - Revenue by Customer (top 5)
  - Revenue Trend Chart

- **Expense Breakdown**
  - Expenses by Category
  - Recent Expenses
  - Expense Trend Chart

- **Debt Summary**
  - Debt Aging Summary
  - Top Debtors (customers)
  - Collection Rate

- **Branch Selector:**
  - Single branch view
  - Consolidated view (if assigned multiple branches)

---

## 6.3 Accountant Flows

### Debt & Collections (Per Branch)

#### Flow 20: View Customer Debt (Per Branch)

**Trigger:** Check customer outstanding balance

**Steps:**
1. Click "Customers" from sidebar
2. Search/find customer
3. Click customer name
4. View customer details:
   - Customer Information
   - Total Purchases (per branch)
   - Total Payments (per branch)
   - Outstanding Balance (per branch)
   - Consolidated Balance (all branches)
   - Payment History
   - Debt Aging
5. Click "View Full Statement"
6. See all transactions with this customer
7. Export statement (PDF/Excel)

**Success Criteria:**
- Customer debt displayed per branch
- Transaction history visible
- Statement exportable

---

#### Flow 21: Record Customer Payment

**Trigger:** Customer makes payment

**Steps:**
1. Click "Collections" from sidebar
2. Click "Record Payment"
3. Select customer
4. **Select branch where payment was received**
5. Select invoice(s) to pay (optional)
6. Enter payment details:
   - Amount Paid (SDG)
   - Amount USD (if applicable)
   - Payment Method (Cash, Bank Transfer, Check, Mobile Money)
   - Transaction Number (for mobile money/bank)
   - Reference Number (for checks)
   - Payment Date
7. Add notes (optional)
8. Click "Record Payment"
9. System:
   - Updates customer balance for the branch
   - Updates invoice status
   - Records payment history
   - Updates debt aging

**Success Criteria:**
- Payment recorded
- Customer branch balance updated
- Invoice status updated
- Payment appears in history

---

#### Flow 22: Review Debt Aging Report (Per Branch)

**Trigger:** Need to prioritize collections

**Steps:**
1. Click "Reports" from sidebar
2. Select "Debt Aging Report"
3. **Select branch or consolidated view**
4. View debt categories:
   - Current (0-30 days)
   - Overdue (30-60 days)
   - Overdue (60-90 days)
   - Overdue (90+ days)
5. Click on aging category to view customers
6. See customer details and amounts
7. Click customer to contact/pursue collection
8. Export report (PDF/Excel)

**Success Criteria:**
- Complete aging report displayed
- Customers by aging category visible
- Export options available
- Customer contact details accessible

---

#### Flow 23: Send Payment Reminder

**Trigger:** Customer payment due/overdue

**Steps:**
1. Navigate to Debt Aging Report
2. Find customer with overdue debt
3. Click "Send Reminder"
4. Select reminder type:
   - Email
   - WhatsApp (if integrated)
   - SMS (if integrated)
5. Customize reminder message (optional)
6. Click "Send Reminder"
7. System:
   - Sends reminder
   - Logs reminder sent
   - Updates customer record

**Success Criteria:**
- Reminder sent to customer
- Log entry created
- Customer notified

---

### Expense Management (Per Branch)

#### Flow 24: Review Expenses

**Trigger:** Check business spending

**Steps:**
1. Click "Expenses" from sidebar
2. **Select branch or consolidated view**
3. View expense list:
   - Filter by category
   - Filter by date range
   - Filter by payment method
4. Click expense to view details
5. View expense summaries
6. Export expense report

**Success Criteria:**
- All expenses visible
- Filtering works
- Export available

---

#### Flow 25: Generate Branch Profit & Loss Report

**Trigger:** Need to assess branch profitability

**Steps:**
1. Click "Reports" from sidebar
2. Select "Profit & Loss Statement"
3. **Select branch or consolidated view**
4. Set date range
5. Select currency (SDG/USD/Both)
6. View report:
   - Revenue Section:
     - Sales Revenue
     - Other Income
     - Total Revenue
   - COGS Section:
     - Cost of Goods Sold
     - Gross Profit
   - Expenses Section:
     - Fixed Expenses
     - Variable Expenses
     - Total Expenses
   - Net Profit/Loss
7. Compare with previous period
8. Export report (PDF/Excel)

**Success Criteria:**
- Accurate P&L generated
- COGS calculated correctly
- All expenses included
- Export available

---

### Financial Reports (Per Branch & Consolidated)

#### Flow 26: Generate Consolidated Profit & Loss Report

**Trigger:** Need to assess overall business profitability

**Steps:**
1. Click "Reports" from sidebar
2. Select "Consolidated Profit & Loss Statement"
3. Set date range
4. Select currency (SDG/USD/Both)
5. View consolidated report:
   - Total Revenue (all branches)
   - Total COGS (all branches)
   - Gross Profit
   - Total Expenses (all branches)
   - Net Profit/Loss
6. View branch breakdown:
   - Revenue by Branch
   - Expenses by Branch
   - Profit by Branch
7. Compare with previous period
8. Export report (PDF/Excel)

**Success Criteria:**
- Consolidated P&L generated
- Branch breakdown visible
- Export available

---

# 7. Warehouse Keeper User Flow

## 7.1 Role Overview

**Role Name:** Warehouse Keeper  
**Access Level:** Inventory Management for assigned branch(es)  
**Primary Responsibility:** Receiving stock, managing inventory, stock transfers, stock counts for their branch  
**Cannot:** See selling prices, see purchase costs, process sales, view financial data  
**Accessible Modules:** Products (view only), Inventory, Inventory Transfers, Shipments (view only), Reports (inventory only)

---

## 7.2 Warehouse Keeper Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter Warehouse Keeper credentials
3. Authenticate
4. Redirected to Warehouse Dashboard (their branch only)

### Dashboard View
The Warehouse Keeper dashboard displays:

- **Inventory KPIs (Their Branch Only)**
  - Total Products in Stock
  - Total Items in Stock
  - Low Stock Items
  - Out of Stock Items
  - Recent Stock Movements
  - Total Stock Value

- **Stock Alerts (Their Branch Only)**
  - Low Stock Alerts
  - Overstock Alerts (optional)
  - Expiring Stock (if applicable)

- **Recent Activity (Their Branch Only)**
  - Recent Stock Receipts
  - Recent Transfers
  - Recent Shipments

- **Quick Actions**
  - Receive Stock
  - Transfer Stock
  - View Low Stock

- **Branch Selector:** (if assigned multiple branches)

---

## 7.3 Warehouse Keeper Flows

### Stock Receiving (Per Branch)

#### Flow 27: Receive Purchase Order

**Trigger:** Products arrive from supplier at their branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Receive Stock"
3. Select or search for purchase order
4. View products to receive
5. Verify received products:
   - Check quantities
   - Check quality/condition
   - Note any damage or shortage
6. Enter receiving details:
   - Received Quantity (may differ from ordered)
   - Batch Number (auto-generated)
   - **Branch is auto-assigned**
   - Storage Location (within branch)
   - Expiry Date (if applicable)
7. Mark any returns/rejects
8. Click "Complete Receiving"
9. System:
   - Creates product batches for the branch
   - Updates inventory quantities for the branch
   - Updates purchase order status
   - Creates stock movement record

**Success Criteria:**
- Products added to branch inventory
- Batches created with costs
- Branch stock quantity updated
- Purchase order updated

---

#### Flow 28: Record Stock Adjustment

**Trigger:** Physical count discrepancy or damaged goods

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Adjust Stock"
3. Select product
4. **Branch is auto-assigned**
5. Select reason:
   - Physical Count Adjustment
   - Damaged Goods
   - Expired Products
   - Sample Withdrawal
   - Other
6. Enter adjustment quantity:
   - Increase stock (positive)
   - Decrease stock (negative)
7. Add notes (required)
8. Click "Apply Adjustment"
9. System:
   - Updates branch stock quantity
   - Creates adjustment record
   - Logs reason and notes

**Success Criteria:**
- Branch stock quantity updated
- Adjustment recorded
- Reason documented
- Audit trail created

---

### Stock Transfer (Between Branches)

#### Flow 29: Request Stock Transfer

**Trigger:** Branch needs stock from another branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Request Stock Transfer"
3. Select product(s)
4. Enter quantity needed
5. Select source branch (from)
6. Add notes (optional)
7. Click "Submit Request"
8. System:
   - Creates transfer request
   - Status = "Pending"
   - Notifies source branch manager/warehouse
9. Track request status

**Success Criteria:**
- Transfer request created
- Source branch notified
- Request appears in transfer list

---

#### Flow 30: Process Outgoing Stock Transfer

**Trigger:** Another branch requests stock from this branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Stock Transfer Requests"
3. View incoming requests
4. Click request to view details:
   - Requesting Branch
   - Products/Quantities
   - Notes
5. Review available stock at your branch
6. **Approve or Reject:**
   - If approve: Confirm quantities, set dispatch date
   - If reject: Provide reason
7. Click "Process Request"
8. If approved:
   - Pick stock from warehouse
   - Pack for transfer
   - Update status to "Dispatched"
9. System:
   - Updates request status
   - Reserves stock (if approved)
   - Notifies requesting branch

**Success Criteria:**
- Request processed
- Stock reserved
- Notification sent

---

#### Flow 31: Receive Incoming Stock Transfer

**Trigger:** Stock transfer arrives from another branch

**Steps:**
1. Click "Inventory" from sidebar
2. Click "Stock Transfer Receiving"
3. View incoming transfers
4. Click transfer to receive
5. Verify received items:
   - Check quantities
   - Check quality/condition
6. Confirm receipt
7. Click "Complete Receiving"
8. System:
   - Updates branch inventory
   - Completes transfer
   - Notifies source branch

**Success Criteria:**
- Stock received at branch
- Branch inventory updated
- Transfer completed

---

### Shipment Management (Per Branch)

#### Flow 32: View Shipments to Pack

**Trigger:** Need to pack orders for shipping from their branch

**Steps:**
1. Click "Shipments" from sidebar
2. Filter by "Pending Processing"
3. View shipments requiring packing
4. Click shipment to view details:
   - Customer Name
   - Products/Quantities
   - Dispatch City (branch location)
   - Delivery City
5. Pack order:
   - Verify product availability
   - Pack boxes/cartons
   - Label boxes
6. Update shipment status to "Ready for Pickup"

**Success Criteria:**
- Shipments visible
- Packing confirmed
- Status updated

---

#### Flow 33: Dispatch Shipment

**Trigger:** Shipment picked up by logistics

**Steps:**
1. Navigate to Shipments
2. Find packed shipment
3. Click "Dispatch"
4. Enter dispatch details:
   - Logistics Company
   - Tracking Number
   - Waybill Number
   - Dispatch Date/Time
   - Number of Boxes
5. Click "Mark as Dispatched"
6. System:
   - Updates shipment status
   - Reduces branch inventory (if not already)
   - Records shipping details

**Success Criteria:**
- Shipment dispatched
- Status updated
- Tracking details recorded
- Branch inventory updated

---

### Inventory Reports (Per Branch)

#### Flow 34: View Branch Inventory Valuation

**Trigger:** Need to know stock value at their branch

**Steps:**
1. Click "Reports" from sidebar
2. Select "Inventory Valuation"
3. **Branch is auto-selected**
4. Set date
5. View valuation:
   - Product Name
   - Quantity on Hand
   - Average Cost
   - Total Value (SDG & USD)
   - By Location (within branch)
6. Export report

**Success Criteria:**
- Branch inventory valuation displayed
- Both currencies shown
- Export available

---

#### Flow 35: View Branch Stock Movement

**Trigger:** Need to track stock changes at their branch

**Steps:**
1. Click "Reports" from sidebar
2. Select "Stock Movement Report"
3. **Branch is auto-selected**
4. Set date range
5. View movements:
   - Product Name
   - Incoming Quantity
   - Outgoing Quantity
   - Net Change
   - Closing Stock
6. Filter by product or location
7. Export report

**Success Criteria:**
- All branch movements shown
- Net change calculated
- Filtering works
- Export available

---

# 8. Sales Staff User Flow

## 8.1 Role Overview

**Role Name:** Sales Staff  
**Access Level:** Sales Processing for assigned branch(es)  
**Primary Responsibility:** Processing sales, customer management, order creation for their branch  
**Cannot:** See purchase costs, change prices (only apply discounts), view financial data  
**Accessible Modules:** Customers, Sales, Shipments (create only), Products (view only), Inventory (view only)

---

## 8.2 Sales Staff Dashboard

### Login & Authentication
1. Navigate to tenant domain
2. Enter Sales Staff credentials
3. Authenticate
4. Redirected to Sales Dashboard (their branch only)

### Dashboard View
The Sales Staff dashboard displays:

- **Sales KPIs (Their Branch Only)**
  - Today's Sales
  - This Week's Sales
  - This Month's Sales
  - Sales Targets (if set)
  - Target Achievement %

- **Recent Sales (Their Branch Only)**
  - Last 10 Sales
  - Quick Reorder Options

- **Customer Quick Actions**
  - Create New Customer
  - Quick Customer
  - Find Customer

- **Inventory Quick View (Their Branch Only)**
  - Low Stock Items
  - Available Stock

---

## 8.3 Sales Staff Flows

### Customer Management (Shared Across Branches)

#### Flow 36: Create Full Customer Account

**Trigger:** Regular customer registration

**Steps:**
1. Click "Customers" from sidebar
2. Click "Add Customer"
3. Fill in customer details:
   - Full Name (required)
   - Phone Number (required)
   - Email (optional)
   - WhatsApp (optional)
   - Address
   - City
   - **Default Branch (optional - auto-assigned to sales staff's branch)**
   - Credit Limit (if applicable)
4. Click "Save Customer"
5. Customer appears in customer list
6. Customer can be selected for future sales

**Success Criteria:**
- Customer created
- Customer searchable
- Available for sales

---

#### Flow 37: Create Quick Customer

**Trigger:** One-time/unregistered customer

**Steps:**
1. Click "Sales" from sidebar
2. Click "New Invoice"
3. Click "Quick Customer"
4. Enter minimal details:
   - Name (required)
   - Phone Number (required)
5. Click "Create"
6. System:
   - Creates quick customer record
   - **Branch assigned automatically**
   - Allows immediate invoicing
7. Proceed with sale

**Note:** Quick customer can be upgraded to full customer later

**Success Criteria:**
- Quick customer created
- Sales can proceed immediately
- Debt linked to customer and branch

---

#### Flow 38: Search Customer

**Trigger:** Need to find existing customer

**Steps:**
1. Click "Customers" from sidebar
2. Type in search box:
   - Name
   - Phone Number
   - Email
3. View search results
4. Click customer name to view details
5. View:
   - Customer Information
   - Purchase History (per branch)
   - Outstanding Balance (per branch)
   - Consolidated Balance (all branches)
   - Debt Status

**Success Criteria:**
- Customer found
- All details visible
- Debt status visible (per branch and consolidated)

---

### Sales Processing (Per Branch)

#### Flow 39: Create New Sale (Cash Sales)

**Trigger:** Customer makes purchase

**Steps:**
1. Click "Sales" from sidebar
2. Click "New Invoice"
3. Select customer:
   - Existing customer (search)
   - Quick customer (create)
4. **Branch is auto-assigned** (sales staff's branch)
5. Add products:
   - Search by name/SKU/barcode
   - Select product
   - Select variant (size/color)
   - Enter quantity
   - View available stock (branch-specific)
6. Review cart:
   - Product Name
   - Quantity
   - Unit Price
   - Total
7. Apply discount (if applicable):
   - Percentage discount
   - Fixed amount discount
8. View total:
   - Subtotal
   - Discount
   - Net Amount
9. Enter payment:
   - Payment Method (Cash, Bank Transfer, etc.)
   - Amount Paid
   - Balance (if credit)
10. Add notes (optional)
11. Click "Complete Sale"
12. System:
    - Creates invoice for the branch
    - Deducts inventory (from branch-specific batch)
    - Records COGS
    - Calculates profit for the branch
    - Updates customer balance (if credit) for the branch
    - Creates debt (if credit) for the branch

**Success Criteria:**
- Invoice created for branch
- Branch inventory updated
- Customer branch balance updated (if credit)
- Invoice can be printed with branch branding
- Profit calculated for branch

---

#### Flow 40: Create Sale on Credit

**Trigger:** Customer buys on credit

**Steps:**
1. Follow steps 1-5 from Flow 39
2. At payment stage:
   - Enter Amount Paid (0 or partial)
   - Balance shows remaining amount
   - Select "Credit" payment status
3. Set due date (optional):
   - Invoice Date + 30 days (default)
   - Custom date
4. Click "Complete Sale"
5. System:
    - Creates invoice with balance for the branch
    - Records debt for the branch
    - Sets due date
    - Adds to branch aging report
    - Updates customer debt balance for the branch

**Success Criteria:**
- Credit invoice created for branch
- Debt recorded for branch
- Due date set
- Customer branch balance updated
- Debt appears in branch aging report

---

#### Flow 41: Apply Discount

**Trigger:** Customer eligible for discount

**Steps:**
1. During sales creation
2. Click "Apply Discount"
3. Select discount type:
   - Percentage (e.g., 10%)
   - Fixed Amount (e.g., 500 SDG)
4. Enter discount value
5. System:
   - Calculates discount amount
   - Updates total
   - Shows discounted total
6. Discount applied to invoice

**Note:** Discount permission is controlled by role. Sales staff can only apply discounts within defined limits.

**Success Criteria:**
- Discount applied
- Total updated
- Discount recorded on invoice

---

#### Flow 42: Check Customer Debt Before Sale

**Trigger:** Customer with existing debt wants to buy

**Steps:**
1. Search for customer
2. View customer details
3. Check outstanding balance:
   - Debt for this branch
   - Consolidated debt (all branches)
4. Review debt aging:
   - Current debt
   - Overdue debt
   - Total debt
5. Make decision:
   - Allow sale on credit (if within limit)
   - Request payment of overdue amount first
   - Require cash payment

**Success Criteria:**
- Customer debt visible (per branch and consolidated)
- Informed decision possible
- Risk minimized

---

### Invoice Management (Per Branch)

#### Flow 43: View Invoice Details

**Trigger:** Need to review invoice

**Steps:**
1. Click "Sales" from sidebar
2. Find invoice:
   - Search by invoice number
   - Filter by date
   - Filter by customer
   - Filter by branch (if multiple branches)
3. Click invoice number
4. View invoice details:
   - Customer Information
   - Products/Quantities
   - Prices
   - Discounts
   - Payment Status
   - Balance (if any)
   - Branch Information
5. Print invoice (with branch branding)
6. Share invoice (email/WhatsApp)

**Success Criteria:**
- Invoice details displayed
- Print available
- Share available

---

### Product & Inventory View (Per Branch)

#### Flow 44: Check Stock Availability

**Trigger:** Customer asks about stock

**Steps:**
1. Click "Products" from sidebar
2. Search product
3. View product details:
   - Product Name
   - Available Stock (per branch)
   - Stock by Location
   - Variants Available
4. Inform customer
5. Proceed with sale

**Success Criteria:**
- Stock visible per branch
- Customer informed
- Accurate stock information

---

### Shipment Management (Per Branch)

#### Flow 45: Create Shipment from Sale

**Trigger:** Customer requires delivery

**Steps:**
1. During/after sale
2. Click "Create Shipment"
3. Fill in shipping details:
   - Customer Name
   - Customer Phone
   - Dispatch City (branch location)
   - Delivery City
   - Logistics Company
   - Number of Boxes/Cartons
   - Shipment Value
   - Shipping Cost
   - Notes
4. **Branch is auto-assigned**
5. Click "Create Shipment"
6. System:
    - Creates shipment record for the branch
    - Status = "Pending Processing"
    - Notifies warehouse (if applicable)
7. Shipment appears in shipping list

**Success Criteria:**
- Shipment created for branch
- Status set
- Warehouse notified
- Tracking possible

---

#### Flow 46: Update Shipment Status (Limited)

**Trigger:** Need to update shipment status

**Steps:**
1. Click "Shipments" from sidebar
2. Find shipment
3. Click "Update Status"
4. Select status:
   - For sales staff: Only "Pending" to "Processing"
   - Higher statuses require warehouse/manager
5. Add notes (optional)
6. Click "Update"
7. Status updated

**Success Criteria:**
- Status updated
- Customer notified (if enabled)
- Audit trail created

---

# 9. Common Flows Across All Roles

## 9.1 Profile Management

### Flow 47: Update Profile

**Trigger:** User needs to update information

**Steps:**
1. Click "Profile" from top menu
2. View profile information
3. Click "Edit Profile"
4. Update details:
   - Name
   - Email
   - Phone
   - Profile Photo
5. Click "Save"
6. System updates profile
7. Confirmation message

**Success Criteria:**
- Profile updated
- Changes reflected immediately

---

### Flow 48: Change Password

**Trigger:** User needs new password

**Steps:**
1. Click "Profile" from top menu
2. Click "Change Password"
3. Enter current password
4. Enter new password
5. Confirm new password
6. Click "Update Password"
7. System validates and updates
8. User logged out (optional)
9. User logs in with new password

**Success Criteria:**
- Password changed
- Security maintained

---

## 9.2 Notification Management

### Flow 49: View Notifications

**Trigger:** User wants to check notifications

**Steps:**
1. Click bell icon (top menu)
2. View notification list:
   - Low stock alerts (per branch)
   - Overdue debt alerts (per branch)
   - Shipment updates (per branch)
   - Stock transfer requests
   - System notifications
3. Click notification to view details
4. Notification marked as read

**Success Criteria:**
- Notifications displayed
- Unread vs. read visible
- Actionable notifications
- Branch context visible

---

### Flow 50: Configure Notification Preferences

**Trigger:** User wants to control notifications

**Steps:**
1. Click "Settings" from profile menu
2. Select "Notifications"
3. Choose notification channels:
   - In-App
   - Email
   - SMS (if available)
   - WhatsApp (if available)
4. Choose notification types:
   - Low Stock Alerts (per branch)
   - Debt Alerts (per branch)
   - Shipment Updates (per branch)
   - Stock Transfer Alerts
   - Payment Received
   - System Updates
5. Click "Save Preferences"
6. Preferences applied

**Success Criteria:**
- Preferences saved
- Notifications follow settings

---

## 9.3 Search & Navigation

### Flow 51: Global Search

**Trigger:** User needs to find something quickly

**Steps:**
1. Click search box (top menu)
2. Type search term:
   - Product name/SKU
   - Customer name/phone
   - Invoice number
   - Supplier name
3. View search results categorized:
   - Products
   - Customers
   - Invoices
   - Suppliers
4. **Search results filtered by user's branch access**
5. Click result to navigate
6. View full details

**Success Criteria:**
- Relevant results displayed
- Quick navigation to details
- Branch context applied

---

### Flow 52: Switch Branch (For Multi-Branch Users)

**Trigger:** User needs to view different branch

**Steps:**
1. Click branch selector (top menu)
2. View available branches:
   - Branch A (assigned)
   - Branch B (assigned)
   - All Branches (if authorized)
3. Select branch
4. System:
   - Updates all data to selected branch
   - Refreshes dashboard
   - Updates KPIs and reports
5. User can work in new branch context

**Success Criteria:**
- Branch switched
- All data updated
- Context preserved across modules

---

# 10. Cross-Role & Cross-Branch Interaction Flows

## 10.1 Complete Multi-Branch Sales Process Flow

### Overview: All roles involved across branches
- **Sales Staff (Branch A):** Creates sale, customer interaction
- **Warehouse Keeper (Branch A):** Packs and dispatches
- **Accountant (Branch A):** Records payment, manages debt
- **Branch Manager (Branch A):** Oversees process
- **Area Manager (Multiple Branches):** Monitors performance
- **HQ Staff:** Consolidated reporting

### Detailed Steps:

**1. Customer Inquiry** (Sales Staff - Branch A)
- Customer asks about products
- Check inventory availability (Branch A)
- Provide pricing information

**2. Create Sale** (Sales Staff - Branch A)
- Create invoice for Branch A
- Add products from Branch A inventory
- Apply discounts (branch-specific policy)
- Record payment (full/partial)

**3. If Credit Sale** (Sales Staff + Accountant - Branch A)
- Debt recorded for Branch A
- Due date set
- Customer branch balance updated

**4. If Shipping Required** (Sales Staff + Warehouse Keeper - Branch A)
- Sales staff creates shipment from Branch A
- Warehouse keeper packs order from Branch A
- Status updates tracking

**5. Payment Collection** (Accountant - Branch A)
- Customer makes payment
- Accountant records payment for Branch A
- Invoice marked as paid (full/partial)

**6. Debt Follow-up** (Accountant - Branch A)
- Monitor aging report for Branch A
- Send reminders
- Escalate overdue accounts

**7. Branch Performance** (Branch Manager - Branch A)
- Review Branch A performance
- Identify areas for improvement

**8. Cross-Branch Oversight** (Area Manager)
- Compare Branch A performance with other branches
- Identify best practices
- Share learnings across branches

**9. Strategic Planning** (HQ Staff)
- View consolidated performance across all branches
- Make strategic decisions
- Allocate resources

---

## 10.2 Complete Multi-Branch Purchase Process Flow

### Overview: All roles involved
- **Branch Manager:** Creates purchase order for branch
- **Warehouse Keeper:** Receives stock at branch
- **Accountant:** Records payment to supplier (branch-specific)

### Detailed Steps:

**1. Identify Need** (Branch Manager - Branch A)
- Check branch inventory levels
- Review branch low stock alerts
- Create purchase order for Branch A

**2. Place Order** (Branch Manager - Branch A)
- Select supplier (shared)
- Add products/quantities
- Submit purchase order for Branch A

**3. Receive Stock** (Warehouse Keeper - Branch A)
- Products arrive at Branch A
- Receive purchase order for Branch A
- Create batches for Branch A
- Update Branch A inventory

**4. Record Payment** (Accountant - Branch A)
- Supplier invoice received
- Record payment for Branch A
- Update supplier balance

---

## 10.3 Complete Stock Transfer Process Flow

### Overview: All roles involved across branches
- **Warehouse Keeper (Branch B):** Requests stock
- **Warehouse Keeper (Branch A):** Processes request
- **Branch Manager (Both Branches):** Oversees transfer
- **Area Manager (Optional):** Approves if needed

### Detailed Steps:

**1. Request Stock** (Warehouse Keeper - Branch B)
- Identify low stock at Branch B
- Create stock transfer request
- Select products/quantities
- Select source branch (Branch A)

**2. Review Request** (Branch Manager / Warehouse Keeper - Branch A)
- Review request from Branch B
- Check availability at Branch A
- Approve or reject

**3. Process Transfer** (Warehouse Keeper - Branch A)
- Pick stock from Branch A inventory
- Pack for transfer
- Update status to "Dispatched"
- Log transfer details

**4. Receive Transfer** (Warehouse Keeper - Branch B)
- Receive stock at Branch B
- Verify quantities
- Confirm receipt
- Update Branch B inventory

**5. Completion** (Both Branches)
- Transfer marked as "Completed"
- Both branch inventories updated
- Transfer history recorded

---

## Summary of Role Abilities (With Branch Scope)

| Feature | Super Admin | Branch Manager | Area Manager | HQ Staff | Accountant | Warehouse | Sales |
|---------|------------|---------------|--------------|----------|------------|-----------|-------|
| Manage Tenants | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Branches | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Users (System) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Users (Branch) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Dashboard | ✅ | ✅ (own) | ✅ (multi) | ✅ (all) | ✅ (assigned) | ✅ (own) | ✅ (own) |
| Manage Products | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Products | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage Inventory | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| View Inventory | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage Stock Transfers | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| View Stock Transfers | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage Suppliers | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Suppliers | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Purchases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Purchases | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Customers | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View Customers | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Manage Sales | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View Sales | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Manage Debt | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| View Debt | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Manage Expenses | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| View Expenses | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Shipping | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ |
| View Shipping | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Branch Reports | ✅ | ✅ (own) | ✅ (multi) | ✅ (all) | ✅ (assigned) | ❌ | ❌ |
| View Consolidated Reports | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Branch Comparison | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Inventory Reports | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| View Sales Reports | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ (own) |
| Manage Settings | ✅ | ✅ (branch) | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Modules | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Custom Fields | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Import/Export | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| View Analytics | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## Branch Context Summary Table

| Feature | Branch User | Area Manager | HQ Staff |
|---------|------------|--------------|----------|
| Default View | Own Branch | Multiple Branches | All Branches |
| Branch Selector | Locked (own) | Available | Available |
| Data Filtering | Auto-filtered | Manual selection | Manual selection |
| Reports | Branch-specific | Multi-branch | Consolidated |
| Stock Management | Own branch only | Across branches | View only |
| Sales | Own branch only | Across branches | View only |
| Customer View | All customers (shared) | All customers | All customers |
| Debt View | Branch-specific | Multi-branch | Consolidated |
| Settings | Branch settings | View only | View only |

---

**End of Document**

---

*This document provides complete user flows for all roles in the ERP system with full multi-branch support. Each flow is detailed with step-by-step actions, success criteria, branch scope, and role-specific abilities.*

---

**© 2026 - All Rights Reserved**