# Wholesale Shoe Store ERP System
## Complete System & Application Requirements Document (AI Plan) - With Multi-Branch Support

**Document Type:** AI Development Plan  
**Version:** 2.0  
**Date:** June 2026  
**Purpose:** This document serves as a complete blueprint for an AI agent to develop a white-label ERP system for wholesale shoe businesses with full multi-branch support.

---

## Document Overview

This document outlines all requirements for building a comprehensive ERP system tailored for the Sudanese market with white-label capabilities and **multi-branch support**. Each branch functions as an independent business unit with its own users, inventory, sales, and financials, while enabling consolidated reporting for headquarters. The system will be built using Laravel as a full-stack framework with Livewire for dynamic interactivity. The document is structured in phases to allow incremental development and testing.

---

# PHASE 0: Project Foundation

## 0.1 Project Summary

### What is This System?
A complete ERP (Enterprise Resource Planning) system for wholesale shoe businesses that can be white-labeled and sold to multiple clients. The system manages all business operations including products, inventory, sales, purchases, customers, suppliers, expenses, shipping, debts, financial reporting, and **multi-branch operations** where each branch operates independently.

### Key Characteristics
- **White-Label Ready:** Can be rebranded for different businesses
- **Multi-Tenant Architecture:** Supports multiple businesses on one installation
- **Multi-Branch Ready:** Each branch functions as an independent business unit
- **Flexible:** Custom fields, module enable/disable, adaptable workflows
- **Interactive:** Live search, real-time notifications, dynamic UI
- **Optimized:** Caching strategy for performance
- **Sudan-Specific:** Handles erratic pricing, multi-currency, no online payments
- **Modular:** Features can be enabled/disabled per client and per branch

### Target Users
- Super Admin (System Owner)
- Business Owner/Manager (Branch or HQ)
- Accountant (Branch or HQ)
- Warehouse Keeper (Branch-specific)
- Sales Staff (Branch-specific)
- Area Manager (Multiple branches)
- HQ Staff (Consolidated view)

### Business Context
- Businesses sell shoes in cartons/packages, not individual pairs
- Prices change frequently (weekly or even daily)
- Dollar rates fluctuate unpredictably
- No online payment gateways are used
- Customers may be unregistered (Quick Customer feature)
- Sales are typically on credit with flexible payment terms
- **Multiple branches** with independent operations and consolidated reporting

---

## 0.2 Core Objectives

1. **Centralized Operations:** All branches managed from one platform
2. **Error Reduction:** Minimize manual data entry errors
3. **Accurate Inventory:** Real-time stock visibility per branch
4. **Enhanced Management:** Complete oversight of sales, purchases, and operations across branches
5. **Efficient Collections:** Streamlined debt management per branch
6. **Real-Time P&L:** Immediate insight into profitability per branch and consolidated
7. **Data-Driven Decisions:** Detailed reports for strategic planning
8. **Increased Efficiency:** Improved workflow and staff productivity
9. **Scalability:** Easily add new branches as the business grows
10. **Flexibility:** Adapts to different business needs and workflows
11. **Branch Autonomy:** Each branch operates independently with its own users and inventory
12. **Consolidated Oversight:** Headquarters can view all branches combined

---

## 0.3 Technology Stack (For Reference)

The AI agent should use the following technology stack:

### Backend
- **Framework:** Laravel 11.x
- **PHP Version:** 8.2 or higher
- **Database:** MySQL 8.0 or higher
- **Cache/Queue:** Redis 7.0 or higher
- **Web Server:** Apache (cPanel compatible)

### Frontend
- **Interactive UI:** Livewire 3.x with Alpine.js
- **Styling:** Tailwind CSS 3.x
- **Icons:** Font Awesome or Heroicons

### Key Libraries (To Be Installed)
- **Permissions:** Spatie/laravel-permission
- **Activity Logs:** Spatie/laravel-activitylog
- **Import/Export:** Maatwebsite/excel
- **Backup:** Spatie/laravel-backup
- **Image Processing:** Intervention/image
- **Notifications:** Laravel Notifications with channels

### Key Features to Implement
- Multi-tenant architecture
- Multi-branch architecture
- Role-based permissions with branch scope
- Audit trail for all actions
- Caching strategy
- Real-time notifications
- Live search functionality
- Import/Export capabilities
- Automated backups
- Multi-language support (Arabic default, English)
- Stock transfers between branches

---

# PHASE 1: Core Infrastructure

## 1.1 Multi-Tenant Architecture

### Requirement: Tenant Management
The system must support multiple businesses (tenants) on a single installation. Each tenant should have isolated data, custom branding, and independent settings.

### Tenant Features
- Create new tenants with unique domain/subdomain
- Each tenant has separate database or data isolation
- Tenant-specific settings (currency, locale, timezone)
- Tenant branding (logo, colors, company name)
- Enable/disable modules per tenant
- Suspension/deactivation of tenants
- Super admin dashboard to manage all tenants

### Tenant Data Fields
- Tenant Name
- Domain/Subdomain
- Database Name (if using separate databases)
- Logo (upload)
- Primary Color (hex code)
- Secondary Color (hex code)
- Is Active (boolean)
- Settings (JSON - locale, timezone, currency, etc.)
- Created At, Updated At

---

## 1.2 Multi-Branch Architecture

### Requirement: Branch Management
Each tenant can have multiple branches. Each branch functions as an independent business unit with complete data isolation for transactions while sharing master data across branches.

### Branch Concept
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

### Branch Features
- Add, edit, suspend branches
- Each branch has independent inventory
- Stock transfers between branches with approval workflow
- Users assigned to specific branches
- Branch-specific sales and purchases
- Branch-specific expenses and shipping
- Branch-specific debt management
- Branch-specific reports
- Consolidated reports for HQ
- Branch performance comparison
- Branch branding (logo, colors, settings)

### Branch Data Fields
- Branch Name (required)
- Branch Code (unique identifier)
- Address
- Phone
- Email
- Manager ID (user assigned as manager)
- Is Active (boolean)
- Settings (JSON - currency, timezone, invoice prefix, etc.)
- Created At, Updated At

---

## 1.3 Module Management

### Requirement: Modular System
The system must be modular so features can be enabled or disabled per tenant. This allows different pricing tiers and customization.

### Core Modules (Always Enabled)
- Dashboard
- Products
- Inventory
- Customers
- Sales
- Expenses

### Optional Modules (Can Be Enabled/Disabled)
- Suppliers
- Purchases
- Debt & Collections
- Shipping & Logistics
- Financial Reports
- Data Analysis & Prediction
- Multi-Branch Support (enables branch management features)

### Module Features
- List all available modules
- Enable/disable modules per tenant
- Module-specific settings per tenant
- Menu dynamically updates based on enabled modules
- Permission checks for module access
- Branch-specific module settings (if multi-branch enabled)

---

## 1.4 Language & Localization

### Requirement: Multi-Language Support
The system must support multiple languages with Arabic as the default and English as secondary.

### Language Features
- Language switching (Arabic ↔ English)
- RTL (Right-to-Left) support for Arabic
- LTR (Left-to-Right) support for English
- Language files for all UI text
- Date format localization
- Number format localization
- Currency symbol localization
- Per branch language settings (optional)

### Language Files Structure
- Arabic: resources/lang/ar/
- English: resources/lang/en/
- All UI text must be translatable
- System detects browser language on first visit
- Users can set preferred language

---

## 1.5 Custom Fields

### Requirement: Flexible Data Structure
The system must support custom fields so businesses can track additional information specific to their needs.

### Custom Field Features
- Add custom fields to Products, Customers, Invoices, Branches
- Field types: Text, Number, Date, Dropdown, Multi-select, Textarea, File Upload
- Mark fields as required or optional
- Set validation rules per field
- Order fields for display
- Searchable custom fields
- Reportable custom fields
- Per branch custom fields (optional)

### Custom Field Configuration
- Model type (product, customer, invoice, branch)
- Field name (internal)
- Field label (displayed)
- Field type (text, number, date, select, etc.)
- Options (for select fields)
- Is required (boolean)
- Is unique (boolean)
- Validation rules
- Display order

---

# PHASE 2: User & Permission Management

## 2.1 User Management

### Requirement: User Accounts
The system must support user accounts with role-based permissions and branch assignment.

### User Features
- User registration and authentication
- User profile management
- Password reset
- Account activation/deactivation
- Last login tracking
- User avatar/photo
- User activity logging
- User search and filtering
- User assignment to one or multiple branches

### User Data Fields
- Name
- Email (unique)
- Phone
- Password (hashed)
- Is Active (boolean)
- Last Login At (timestamp)
- Tenant ID (for multi-tenant)
- Profile Photo (optional)
- Default Branch (optional)

---

## 2.2 Role & Permission Management

### Requirement: Granular Permissions with Branch Scope
The system must have a comprehensive role-based permission system with branch-level access control.

### Default Roles
| Role | Description | Access Level | Branch Scope |
|------|-------------|--------------|--------------|
| Super Admin | Full system access | Everything | All Branches, All Tenants |
| Admin | Business owner/manager | Everything except system settings | Own Branch Only |
| Accountant | Financial management | Financial modules, reports | Assigned Branch(es) |
| Warehouse Keeper | Inventory management | Products, inventory, shipments | Assigned Branch(es) |
| Sales Staff | Sales processing | Sales, customers, can view debt | Assigned Branch(es) |
| Area Manager | Oversee multiple branches | All modules, consolidated reports | Multiple Branches |
| HQ Staff | View all branches | All modules, consolidated reports | All Branches |

### Permission Categories
- **Dashboard:** view_dashboard
- **Users:** view_users, create_users, edit_users, delete_users
- **Roles:** view_roles, create_roles, edit_roles, delete_roles
- **Products:** view_products, create_products, edit_products, delete_products, view_product_costs
- **Sales:** view_sales, create_sales, edit_sales, delete_sales, view_sales_reports
- **Purchases:** view_purchases, create_purchases, edit_purchases, delete_purchases
- **Customers:** view_customers, create_customers, edit_customers, delete_customers, view_customer_debts
- **Suppliers:** view_suppliers, create_suppliers, edit_suppliers, delete_suppliers
- **Inventory:** view_inventory, manage_inventory, transfer_inventory, view_inventory_reports
- **Expenses:** view_expenses, create_expenses, edit_expenses, delete_expenses, view_expense_reports
- **Shipping:** view_shipments, create_shipments, edit_shipments, delete_shipments, view_shipping_reports
- **Debt:** view_debts, manage_collections, view_debt_reports
- **Financial:** view_financial_reports, view_profit_loss, view_balance_sheet
- **Settings:** view_settings, edit_settings
- **Import/Export:** import_data, export_data
- **Backup:** manage_backups
- **Modules:** manage_modules
- **Analytics:** view_analytics
- **Branches:** view_branches, manage_branches
- **Stock Transfers:** request_stock_transfer, approve_stock_transfer

### Branch-Specific Permissions
- **view_branch_inventory:** View inventory at own branch
- **manage_branch_inventory:** Manage inventory at own branch
- **view_all_inventory:** View all branch inventory (HQ/Area)
- **transfer_stock:** Transfer stock to/from branches
- **view_branch_sales:** View own branch sales
- **view_all_sales:** View all branch sales (HQ/Area)
- **view_branch_financials:** View own branch financials
- **view_all_financials:** View all branch financials (HQ/Area)

### Permission Features
- Create custom roles
- Assign multiple permissions to roles
- Assign roles to users
- Assign users to branches
- Check permissions in views and controllers
- Permission inheritance
- Branch-scoped permissions

---

## 2.3 Activity Logging & Audit Trail

### Requirement: Complete Audit Trail
Every critical action in the system must be logged for accountability and security.

### Logging Features
- Log user actions (create, update, delete, login, logout)
- Log model changes (what was changed, from what to what)
- Log IP address and user agent
- Log timestamp
- Log branch context (which branch the action occurred in)
- View activity logs
- Search and filter logs
- Export logs
- Per branch activity logs

### Actions to Log
- User login/logout
- Create, update, delete for all major models
- Price changes
- Payment entries
- Shipment status changes
- Permission changes
- System settings changes
- Branch changes (create, update, suspend)
- Stock transfers between branches

---

# PHASE 3: Product & Inventory Management

## 3.1 Product Management

### Requirement: Comprehensive Product Catalog
The system must manage all products with variants, categories, brands, and historical pricing. Products are shared across all branches.

### Product Features
- Add, edit, delete products
- Bulk product entry (enter total cost for batch)
- Individual product entry
- Product variants (size, color, etc.)
- Product categories (sport, casual, formal, etc.)
- Product sub-categories (running, training, etc.)
- Product brands
- Product description
- Product images (multiple)
- Product barcode/QR code
- Product SKU (auto-generated or manual)
- Product status (active/inactive)
- Products are shared across all branches

### Product Data Fields
- Name
- SKU (unique)
- Barcode
- Category ID
- Brand ID
- Description
- Purchase Price (SDG)
- Purchase Price USD
- Selling Price (SDG)
- Selling Price USD
- Reorder Level (minimum stock per branch)
- Minimum Order Quantity
- Is Active
- Weight
- Dimensions

### Product Variants
- Size (EU, US, UK)
- Color
- SKU per variant
- Barcode per variant
- Additional info (JSON)

### Product Categories
- Category name
- Parent category (for sub-categories)
- Description
- Category image (optional)

### Product Brands
- Brand name
- Description
- Brand logo (optional)

---

## 3.2 Inventory Management

### Requirement: Real-Time Stock Control Per Branch
The system must track inventory accurately across multiple branches with batch tracking.

### Inventory Features
- Track stock in real-time per branch
- Multiple storage locations within each branch
- Transfer stock between branches
- Batch tracking for accurate COGS
- Stock reception (add new quantities to specific branch)
- Physical inventory count per branch
- Stock adjustment (increase/decrease) per branch
- Low stock alerts per branch
- Slow-moving product identification per branch
- Stock valuation (SDG and USD) per branch

### Branch Inventory Features
- Each branch maintains its own stock
- Branch-specific stock levels and alerts
- Branch-specific stock valuation
- View stock by product, brand, category, location, branch
- Consolidated stock view (HQ)

### Stock Transfer Between Branches
- Request stock from another branch
- Approve stock transfer requests
- Track transfers (pending, approved, in-transit, received, cancelled)
- Transfer history and reports
- Automatic inventory updates on transfer completion

### Batch Tracking Features
- Each product has multiple batches per branch
- Each batch has: quantity, remaining quantity, cost per unit, purchase date, branch
- FIFO (First-In, First-Out) or manual batch selection
- Link sales to specific batches
- Historical cost tracking
- Batches are branch-specific

### Batch Data Fields
- Product ID
- Variant ID (optional)
- Supplier ID
- Purchase Order ID
- Batch Number
- Quantity
- Remaining Quantity
- Cost Per Unit (SDG)
- Cost Per Unit USD
- Purchase Date
- Expiry Date (optional)
- Branch ID (which branch owns the batch)
- Location ID (within branch)

### Stock Movement Features
- Stock In (receptions per branch)
- Stock Out (sales, transfers, adjustments per branch)
- Stock Transfer between branches
- Stock Adjustment (increase/decrease) per branch
- Stock Return (from customers) per branch
- Real-time stock updates per branch

### Low Stock Alert
- Set reorder level per product per branch
- Automatic notification when stock falls below reorder level
- Dashboard widget showing low stock items per branch
- Consolidated low stock view (HQ)
- Email notification (optional)

---

# PHASE 4: Supplier & Purchase Management

## 4.1 Supplier Management

### Requirement: Supplier Database
The system must manage all suppliers and track transactions with them. Suppliers are shared across branches.

### Supplier Features
- Add, edit, delete suppliers
- Supplier contact information
- Payment terms
- Purchase history per branch
- Outstanding balance (payables) per branch
- Supplier rating
- Document attachments (contracts, etc.)
- Suppliers are shared across all branches

### Supplier Data Fields
- Name
- Phone
- Email
- Address
- Contact Person
- Payment Terms
- Rating
- Notes

---

## 4.2 Purchase Management

### Requirement: Complete Procurement System Per Branch
The system must manage all purchase orders and procurement activities per branch.

### Purchase Features
- Create purchase orders per branch
- Add products and quantities
- Enter costs in SDG and USD
- Track payment status (unpaid, partial, paid)
- Link to suppliers
- Link to batches
- Complete purchase history per branch
- Purchase reports per branch and consolidated

### Purchase Order Data
- Supplier ID
- Branch ID (which branch is purchasing)
- PO Number (auto-generated)
- Order Date
- Expected Delivery Date
- Total Amount (SDG)
- Total Amount USD
- Paid Amount (SDG)
- Payment Status (unpaid, partial, paid)
- Order Status (draft, ordered, received, partial, cancelled)
- Notes
- Created By

### Purchase Items Data
- Product ID
- Variant ID
- Quantity
- Received Quantity
- Cost Per Unit (SDG)
- Cost Per Unit USD
- Total (SDG)
- Total USD

### Purchase Reports
- By supplier
- By date range
- By product
- By payment status
- By branch
- Consolidated (HQ view)
- Purchase summary

---

# PHASE 5: Customer & Sales Management

## 5.1 Customer Management

### Requirement: Customer Database
The system must manage customers including quick/unregistered customers. Customers are shared across branches.

### Customer Features
- Full customer accounts
- Quick customer creation (Name + Phone only)
- Customer transaction history per branch
- Customer balance tracking per branch
- Consolidated customer balance (HQ view)
- Customer badges based on purchase volume
- Customer debt tracking per branch
- Customer account statements per branch
- Customers can have a preferred/default branch

### Customer Data Fields
- Name (required)
- Phone (required)
- Email (optional)
- WhatsApp (optional)
- Address
- City
- Is Quick Customer (boolean)
- Balance (total debt per branch)
- Consolidated Balance (all branches)
- Credit Limit
- Badge (based on purchase volume)
- Notes
- Default Branch (preferred branch)

### Quick Customer Feature
- Minimal fields: Name and Phone
- Auto-creates customer record
- Can be upgraded to full customer later
- All debt linked to quick customer
- Debt tracked per branch

### Customer Badges
- Auto-assign based on purchase volume per branch
- Consolidated badges (all branches)
- Manual override by admin
- Can influence pricing

---

## 5.2 Sales Management

### Requirement: Complete Sales System Per Branch
The system must process sales quickly with flexible pricing and discount options per branch.

### Sales Features
- Create sales invoices per branch
- Add products from inventory (branch-specific)
- Batch selection during sale (branch-specific batches)
- Flexible discount options (percentage, fixed amount)
- Track paid and outstanding amounts per branch
- Support for carton/package sales
- Invoice printing and sharing with branch branding
- Invoice history per branch
- Sales reports per branch and consolidated

### Sales Invoice Data
- Customer ID (can be null for quick customers)
- Branch ID (which branch made the sale)
- Invoice Number (auto-generated with branch prefix)
- Invoice Date
- Due Date (for credit sales)
- Total Amount (SDG)
- Total Amount USD
- Discount Type (percentage, fixed)
- Discount Value
- Discount Amount
- Net Amount (SDG)
- Net Amount USD
- Paid Amount (SDG)
- Balance (SDG)
- Payment Status (paid, partial, unpaid)
- Payment Method (cash, bank_transfer, check, mobile_money)
- Transaction Number (for mobile money/bank)
- Exchange Rate (at time of sale)
- Notes
- Created By

### Sales Items Data
- Product ID
- Variant ID
- Batch ID (specific batch sold from branch)
- Quantity
- Unit Price (SDG)
- Unit Price USD
- Cost Per Unit (COGS from batch)
- Discount Type (percentage, fixed)
- Discount Value
- Total (SDG)
- Total USD

### Discount Options
- Percentage discount (e.g., 10%)
- Fixed amount discount (e.g., 500 SDG)
- Manual price override (admin only)
- Seller can only apply discounts, cannot change base price
- Branch-specific discount policies (optional)

### Payment Methods
- Cash
- Bank Transfer (with transaction number)
- Check (with check number)
- Mobile Money (MBok, Bankak with transaction number)
- Payment method-specific fields

### Sales Reports
- By date range
- By customer
- By product
- By brand
- By payment status
- By branch
- Consolidated (HQ view)
- Sales summary
- Top-selling products per branch
- Top-selling brands per branch
- Branch comparison sales report

---

# PHASE 6: Debt Management & Collections

## 6.1 Debt Management

### Requirement: Complete Debt Tracking Per Branch
The system must track all customer debts and provide collection tools per branch.

### Debt Features
- Track all customer outstanding balances per branch
- Link debts to specific invoices
- Due date management (invoice date + 30 days OR custom due date)
- Automatic overdue flagging after 30 days
- Payment recording per branch
- Debt aging reports per branch and consolidated
- Collection notifications per branch

### Debt Aging Reports
- Current (0-30 days) - per branch and consolidated
- Overdue (30-60 days) - per branch and consolidated
- Overdue (60-90 days) - per branch and consolidated
- Overdue (90+ days) - per branch and consolidated
- Branch comparison aging report

### Collection Features
- Record customer payments per branch
- Link payments to invoices
- Track collection history per branch
- Collection performance reports per branch
- Consolidated collection performance (HQ)
- Payment reminders per branch

### Payment Recording
- Customer ID
- Branch ID (which branch received payment)
- Invoice ID (optional)
- Amount (SDG)
- Amount USD (optional)
- Payment Method (cash, bank_transfer, check, mobile_money)
- Transaction Number
- Reference Number (check number, etc.)
- Payment Date
- Notes
- Recorded By

---

# PHASE 7: Expense Management

## 7.1 Expense Tracking

### Requirement: Complete Expense Management Per Branch
The system must track all business expenses for accurate profit calculation per branch.

### Expense Features
- Record daily expenses per branch
- Categorize expenses
- Enter amount in SDG (and optionally USD)
- Link expenses to specific actions (purchase, shipment, etc.)
- Attach receipt images
- Expense reports per branch and consolidated

### Expense Categories
- Fixed Expenses:
  - Rent (per branch)
  - Salaries & Wages (per branch)
  - Utilities (Electricity, Water) (per branch)
  - Internet & Telecommunications (per branch)
- Variable Expenses:
  - Transport & Logistics (per branch)
  - Maintenance (per branch)
  - Marketing (per branch)
  - Miscellaneous (per branch)

### Expense Data
- Expense Category ID
- Branch ID (which branch incurred the expense)
- Reference ID (link to purchase, shipment, etc.)
- Reference Type (purchase_order, shipment, etc.)
- Amount (SDG)
- Amount USD (optional)
- Description
- Expense Date
- Payment Method
- Receipt Number
- Receipt Image
- Recorded By

### Expense Linking Options
- **Option A:** Link to specific invoice/order (deduct from that transaction's profit)
- **Option B:** Global expense (deduct globally at end of period)
- Both options supported
- Admin can set default method
- Per branch settings

### Expense Reports
- By category
- By date range
- By linked action
- By branch
- Consolidated (HQ view)
- Comparative analysis
- Expense summary
- Branch comparison expense report

---

# PHASE 8: Shipping & Logistics

## 8.1 Shipping Management

### Requirement: Complete Shipping System Per Branch
The system must manage all shipments to customers with tracking and cost management per branch.

### Shipping Features
- Create shipping requests from sales invoices
- Track shipment status per branch
- Manage logistics companies
- Track shipping costs per branch
- Handle shipment returns per branch
- Attach proof of delivery (POD)
- Shipping reports per branch and consolidated

### Shipping Request Data
- Sales Invoice ID
- Customer ID
- Branch ID (which branch is shipping)
- Logistics Company ID
- Tracking Number
- Waybill Number
- Dispatch City (branch location)
- Delivery City
- Number of Boxes/Cartons
- Shipment Value
- Shipping Cost (SDG)
- Shipping Cost USD (optional)
- Status
- POD Image (proof of delivery)
- Notes
- Dispatched At
- Delivered At
- Created By

### Shipment Statuses
- Pending Processing
- Handed to Logistics Company
- In Transit
- Arrived at Destination City
- Delivered / Received
- Returned

### Logistics Companies
- Company Name
- Phone
- Email
- Address
- Contact Person
- Rating (1-5)
- Notes
- Branch assignment (optional)

### Shipping Cost Deduction Options
- **Option A:** Per-invoice deduction (deduct from specific sale profit)
- **Option B:** Global deduction (deduct from total revenue)
- Both options supported
- Admin sets default method in settings
- Per branch settings

### Shipment Returns
- Return from Shipment document
- Product ID
- Variant ID
- Batch ID
- Quantity
- Reason
- Status (pending, approved, rejected, processed)
- Processed By
- Processed At

### Shipping Reports
- Completed shipments per branch
- Pending shipments per branch
- Returned shipments per branch
- Shipping cost analysis per branch
- Most requested cities per branch
- Most used logistics companies per branch
- Shipping cost per order per branch
- Consolidated shipping reports (HQ)

---

# PHASE 9: Financial Reports & Analytics

## 9.1 Financial Reporting

### Requirement: Comprehensive Financial Reports Per Branch and Consolidated
The system must provide complete financial reporting for informed decision-making per branch and consolidated.

### Financial Reports
1. **General Financial Report:** Complete financial position summary (per branch and consolidated)
2. **Profit & Loss Statement:** Net profit calculation for any period (per branch and consolidated)
3. **Revenue Report:** Revenue analysis by time period (per branch and consolidated)
4. **Expense Report:** Expense breakdown by type and period (per branch and consolidated)
5. **Debt Report:** All outstanding customer receivables (per branch and consolidated)
6. **Supplier Report:** All outstanding supplier payables (per branch and consolidated)
7. **Cash Flow Report:** Daily financial transaction history (per branch and consolidated)
8. **Periodic Comparison Report:** Side-by-side comparison across periods (per branch and consolidated)
9. **Branch Performance Report:** Compare all branches side-by-side (consolidated)
10. **Branch Comparison Report:** Side-by-side comparison across branches (consolidated)

### Report Features
- Date range filtering
- SDG and USD views
- Branch filtering (single branch, multiple branches, consolidated)
- Export to PDF, Excel, CSV
- Chart visualizations
- Print functionality
- Customizable columns

### Periodic Comparison
- Compare 30 days, 3 months, 1 year, custom ranges
- Show: Revenue, COGS, Expenses, Net Profit
- Side-by-side comparison
- Percentage change indicators
- Per branch and consolidated

### Cash Flow Management
- Track cash receipts and payments per branch
- Daily cash flow per branch
- Opening and closing balances per branch
- Consolidated cash flow (HQ)
- Cash flow forecast (optional)

---

# PHASE 10: Data Analysis & Prediction

## 10.1 Analytics Dashboard

### Requirement: Data-Driven Insights Per Branch and Consolidated
The system must analyze historical data to provide predictive insights per branch and consolidated.

### Analytics Features
- Sales prediction based on historical data (per branch and consolidated)
- Product performance analysis (per branch and consolidated)
- Brand/category performance (per branch and consolidated)
- Customer behavior analysis (per branch and consolidated)
- Inventory optimization (per branch)
- Revenue and profit forecasting (per branch and consolidated)
- Branch performance analysis

### Sales Prediction
- Analyze historical sales patterns per branch
- Predict future sales volumes per branch
- Seasonal pattern identification per branch
- Growth trend analysis per branch
- Consolidated prediction (HQ)

### Product Performance
- Best-selling products per branch
- Best-selling brands per branch
- Best-performing categories per branch
- Slow-moving products per branch
- Product lifecycle prediction per branch
- Consolidated product performance (HQ)

### Customer Analysis
- Customer lifetime value per branch
- Customer churn prediction per branch
- Segment analysis per branch
- Repeat purchase patterns per branch
- Consolidated customer analysis (HQ)

### Inventory Optimization
- Optimal stock level recommendations per branch
- Stockout date predictions per branch
- Reorder quantity suggestions per branch
- Overstock reduction strategies per branch

### Branch Performance Analysis
- Compare performance across branches
- Identify best-performing branches
- Identify underperforming branches
- Recommend improvements for underperforming branches
- Branch ranking reports

---

# PHASE 11: Import/Export & Backup

## 11.1 Import/Export

### Requirement: Data Migration
The system must support bulk data import and export per branch and consolidated.

### Import Features
- Import products from CSV/Excel
- Import customers from CSV/Excel
- Import suppliers from CSV/Excel
- Import inventory from CSV/Excel (per branch)
- Import expenses from CSV/Excel (per branch)
- CSV/Excel mapping
- Validation before import
- Import history and errors
- Branch assignment during import

### Export Features
- Export products to CSV/Excel
- Export customers to CSV/Excel
- Export sales to CSV/Excel (per branch and consolidated)
- Export purchases to CSV/Excel (per branch and consolidated)
- Export expenses to CSV/Excel (per branch and consolidated)
- Export reports to PDF, Excel, CSV (per branch and consolidated)
- Export financial reports (per branch and consolidated)

### Export Formats
- CSV (comma separated)
- Excel (.xlsx)
- PDF (reports)
- Print (HTML)

---

## 11.2 Backup & Restore

### Requirement: Data Protection
The system must protect data through automated backups.

### Backup Features
- Automated scheduled backups (daily/weekly)
- Manual on-demand backup
- Database backup
- File backup (uploads, documents)
- Backup storage options
- Backup restore
- Backup encryption
- Backup logs
- Per tenant and per branch backup options

### Backup Types
- Database backup (SQL dump)
- File backup (uploads, attachments)
- Full backup (database + files)

### Backup Storage
- Local storage
- Cloud storage (AWS S3, Google Drive)
- Remote FTP (optional)

### Restore Features
- Restore from backup
- Partial restore (database only, files only)
- Restore history

---

# PHASE 12: Notifications & Real-Time Features

## 12.1 Notification System

### Requirement: Real-Time Notifications Per Branch
The system must provide real-time notifications for critical events per branch.

### Notification Features
- In-app notifications
- Email notifications
- WhatsApp notifications (optional)
- SMS notifications (optional)
- Per branch notifications

### Notification Triggers
- Low stock alerts per branch
- Overdue debt alerts per branch
- Due date reminders per branch
- Shipment status updates per branch
- Payment received per branch
- New purchase order per branch
- Stock transfer requests and approvals
- System events
- Consolidated alerts (HQ)

### Notification Types
- **Alert:** Critical, requires attention (e.g., low stock, overdue debt)
- **Reminder:** Upcoming events (e.g., payment due tomorrow)
- **Info:** Informational (e.g., shipment delivered)
- **Success:** Positive events (e.g., payment received)
- **Transfer:** Stock transfer related notifications

### Notification Preferences
- Users can choose notification channels
- Users can choose which events to be notified about
- Tenant-level notification settings
- Branch-level notification settings

---

## 12.2 Real-Time Features

### Requirement: Dynamic User Experience
The system must provide a responsive, dynamic user experience with branch context.

### Live Search Features
- Search as you type
- Search across multiple fields
- Search across multiple models
- Autocomplete suggestions
- Branch-specific search results

### Live Updates
- Dashboard updates in real-time per branch
- Stock updates in real-time per branch
- Notification updates in real-time
- Invoice preview updates
- Branch context preserved

### Interactive Features
- Modal forms with validation
- Inline editing
- Drag-and-drop (for reordering, etc.)
- Dynamic filters
- Pagination with live updates
- Branch selector with live switch

---

# PHASE 13: System Settings & Customization

## 13.1 System Settings

### Requirement: Configurable System
The system must have comprehensive settings for customization per tenant and per branch.

### General Settings
- Company name
- Company logo
- Primary color
- Secondary color
- Favicon
- Date format
- Timezone
- Language
- Per branch settings (override parent)

### Branch Settings
- Branch Name
- Branch Code
- Branch Logo
- Branch Primary/Secondary Colors
- Branch Address, Phone, Email
- Default Currency per branch
- Invoice Prefix per branch
- Branch-specific timezone (optional)
- Branch-specific language (optional)

### Currency Settings
- Default currency (SDG or USD)
- Exchange rate (manual entry)
- Currency symbol position
- Decimal places
- Per branch currency settings (optional)

### Invoice Settings
- Invoice prefix (global and per branch)
- Invoice number format
- Invoice template
- Logo on invoice (global and per branch)
- Footer text on invoice (global and per branch)

### Inventory Settings
- Default reorder level
- Stock alert threshold
- Unit of measurement
- Per branch inventory settings

### Permission Settings
- Default roles
- Permission assignments
- Price override permissions
- Branch-specific permissions

### Module Settings
- Enable/disable modules per tenant
- Enable/disable modules per branch
- Module-specific settings

---

## 13.2 Customization Features

### Requirement: Flexible Customization
The system must allow customization for different business needs.

### Branding Customization
- Upload company logo (global and per branch)
- Change primary/secondary colors (global and per branch)
- Custom domain (optional)
- White-label reports (show company logo)
- Per branch branding

### Template Customization
- Invoice templates (global and per branch)
- Report templates
- Email templates
- Notification templates
- Per branch templates

### Workflow Customization
- Enable/disable features
- Custom order statuses
- Custom payment methods
- Custom shipping methods
- Branch-specific workflows

---

# PHASE 14: Performance Optimization

## 14.1 Caching Strategy

### Requirement: Optimized Performance
The system must implement caching for optimal performance with multi-branch support.

### Cache Types
- **Query Cache:** Cache frequent database queries
- **View Cache:** Cache rendered views
- **Session Cache:** Cache user sessions
- **Configuration Cache:** Cache configuration
- **Branch Context Cache:** Cache branch-specific data

### Cache Strategy
- Cache products list (shared across branches)
- Cache categories (shared across branches)
- Cache brands (shared across branches)
- Cache branch-specific inventory data
- Cache dashboard KPIs per branch
- Cache reports (regenerated on demand)
- Clear cache on data changes
- Branch-specific cache invalidation

### Cache TTL
- Products: 1 hour
- Categories: 24 hours
- Brands: 24 hours
- Branch inventory: 15 minutes
- Dashboard KPIs: 5 minutes
- Reports: 30 minutes

---

## 14.2 Database Optimization

### Requirement: Database Performance
The system must optimize database queries and structure with multi-branch support.

### Optimization Strategies
- Add indexes on frequently searched columns
- Use eager loading to avoid N+1 queries
- Use query scopes for reusable queries
- Optimize joins and subqueries
- Use pagination for large datasets
- Soft deletes for data retention
- Branch filtering optimization

### Key Indexes
- Products: name, sku, barcode, category_id, brand_id
- Customers: name, phone, email
- Sales: invoice_number, customer_id, invoice_date, branch_id
- Inventory: product_id, location_id, branch_id
- Payments: customer_id, invoice_id, branch_id
- Batches: product_id, branch_id, purchase_date

---

## 14.3 Performance Features

### Required Performance Features
- **Pagination:** All lists must use pagination
- **Lazy Loading:** Only load data when needed
- **Lazy Loading of Images:** Load images on scroll
- **Minification:** CSS and JS minified
- **Browser Caching:** Leverage browser caching
- **CDN:** Use CDN for static assets
- **Branch Context:** Efficient branch data loading

### Load Testing Goals
- Support 100+ concurrent users per branch
- Page load under 2 seconds
- API responses under 500ms
- Report generation under 5 seconds
- Support multiple branches simultaneously

---

# PHASE 15: Testing & Deployment

## 15.1 Testing Requirements

### Required Testing Types
- **Unit Testing:** Test individual components
- **Feature Testing:** Test complete features
- **Integration Testing:** Test system integration
- **User Acceptance Testing (UAT):** Client testing
- **Multi-Branch Testing:** Test branch isolation and consolidation

### Test Coverage Goals
- Core features: 90% coverage
- Business logic: 85% coverage
- Critical paths: 100% coverage
- Branch features: 90% coverage

### Key Test Scenarios
- User registration and login per branch
- Product CRUD operations
- Sales process (create, discount, payment) per branch
- Inventory movements (receive, transfer, sell) per branch
- Stock transfers between branches
- Debt management (create invoice, record payment, aging) per branch
- Shipping process (create, update status, return) per branch
- Branch switching and data isolation
- Consolidated reporting

---

## 15.2 Deployment Requirements

### Environment Setup
- Development environment
- Staging environment
- Production environment

### Deployment Process
- Code version control (Git)
- CI/CD pipeline (GitHub Actions/GitLab CI)
- Automated testing before deployment
- Zero-downtime deployment

### Post-Deployment Checklist
- Verify database migrations
- Verify seeders
- Verify file permissions
- Verify cache clear
- Verify config clear
- Verify queue workers running
- Verify scheduler running
- Verify backup schedule
- Verify branch data isolation
- Verify stock transfer functionality

---

## 15.3 Documentation Requirements

### Required Documentation
- User Manual (for end users)
- Admin Manual (for system admins)
- Installation Guide
- API Documentation (if API is exposed)
- Developer Documentation (code documentation)
- Branch Management Guide

### User Manual Contents
- Getting started
- Dashboard overview with branch context
- Each module guide
- Branch switching and context
- How-to guides
- Troubleshooting
- FAQ

---

# PHASE 16: Additional Features

## 16.1 White-Label Dashboard

### Requirement: Super Admin Dashboard
The system must have a dashboard for the system owner to manage all tenants and their branches.

### Super Admin Features
- List all tenants
- Create/Edit/Suspend tenants
- View tenant status
- View tenant branches
- View system health
- View system metrics (total tenants, total users, total branches, etc.)
- System settings

### System Health Monitoring
- Server status (CPU, Memory, Disk)
- Database status
- Queue status
- Cache status
- Error logs
- Per tenant and per branch status

---

## 16.2 Branch Management Dashboard

### Requirement: Branch Management Interface
The system must provide a comprehensive interface for managing branches.

### Branch Management Features
- List all branches per tenant
- Create/Edit/Suspend branches
- View branch status
- View branch metrics (users, inventory, sales, etc.)
- Branch settings
- Branch user assignment
- Branch stock transfer monitoring

---

# PHASE 17: Security Features

## 17.1 Security Requirements

### Required Security Features
- **Authentication:** Secure login with password hashing
- **Authorization:** Role-based permissions with branch scope
- **CSRF Protection:** All forms protected
- **XSS Protection:** Input sanitization
- **SQL Injection Prevention:** Parameterized queries
- **Rate Limiting:** Prevent brute force attacks
- **Session Management:** Secure session handling
- **Password Policy:** Strong password requirements
- **Two-Factor Authentication:** (Optional)

### Password Policy
- Minimum 8 characters
- Require uppercase and lowercase
- Require numbers
- Require special characters (optional)

### Session Security
- Session timeout (default 2 hours)
- Session regeneration on login
- HTTPS required for production
- Secure cookie flags
- Branch context preserved in session

---

## 17.2 Data Security

### Data Encryption
- Encrypt sensitive data at rest
- Encrypt data in transit (HTTPS)
- Encrypt backups

### Data Privacy
- GDPR compliance (optional)
- Data isolation per tenant and per branch
- User data access logs
- Branch data isolation

### Branch Data Isolation
- Users only see data from their assigned branches
- Branch managers cannot see other branches
- HQ users can see all branches
- Stock transfers maintain data integrity
- Audit trail shows branch context

---

# Appendices

## Appendix A: Key Features Checklist

- [x] Multi-tenant architecture
- [x] Multi-branch architecture
- [x] Role-based permissions with branch scope
- [x] User management with branch assignment
- [x] Product management with variants
- [x] Batch tracking for COGS
- [x] Inventory management per branch
- [x] Stock transfers between branches
- [x] Multiple storage locations per branch
- [x] Supplier management (shared)
- [x] Purchase order management per branch
- [x] Customer management (including Quick Customer) (shared)
- [x] Sales management per branch with flexible discounts
- [x] Debt management and collections per branch
- [x] Debt aging reports per branch and consolidated
- [x] Expense management per branch
- [x] Shipping and logistics per branch
- [x] Financial reports per branch and consolidated
- [x] Branch performance comparison
- [x] Profit & Loss statement per branch and consolidated
- [x] Data analysis and prediction per branch and consolidated
- [x] Import/Export (CSV, Excel)
- [x] Backup and restore
- [x] Notifications (in-app, email) per branch
- [x] Real-time live search with branch context
- [x] Multi-language (Arabic, English)
- [x] Custom fields
- [x] Module enable/disable
- [x] Caching strategy
- [x] Audit trail with branch context
- [x] White-label branding

## Appendix B: Business Workflow Examples

### Multi-Branch Sales Workflow
1. Customer places order at Branch A
2. Sales staff at Branch A creates invoice
3. Selects products and quantities from Branch A inventory
4. Applies discount (if any)
5. Records payment (full or partial)
6. If credit, debt is recorded under Branch A
7. If shipping needed, creates shipment from Branch A
8. Invoice is printed with Branch A branding
9. HQ can view consolidated sales across all branches

### Multi-Branch Inventory Workflow
1. Purchase order created for Branch A
2. Products received at Branch A
3. Batches created with costs under Branch A
4. Products added to Branch A inventory
5. Branch B requests stock transfer from Branch A
6. Branch A approves and dispatches stock
7. Stock is in transit
8. Branch B receives and confirms
9. Both branch inventories updated
10. Transfer recorded in transfer history

### Multi-Branch Debt Collection Workflow
1. Invoice created with credit at Branch A
2. Due date set (invoice date + 30 days)
3. Debt appears in Branch A aging report
4. Reminders sent before due date
5. After 30 days, flagged as overdue in Branch A
6. Collections team at Branch A follows up
7. Payment recorded at Branch A
8. HQ can view consolidated debt across all branches

## Appendix C: Report List

| # | Report Name | Description | Branch Scope |
|---|-------------|-------------|--------------|
| 1 | Sales Report | Sales by date, customer, product, brand | Per Branch / Consolidated |
| 2 | Purchase Report | Purchases by date, supplier, product | Per Branch / Consolidated |
| 3 | Inventory Report | Current stock, value, low stock items | Per Branch |
| 4 | Debt Aging Report | Customer debts by aging category | Per Branch / Consolidated |
| 5 | Expense Report | Expenses by category, date, linked action | Per Branch / Consolidated |
| 6 | Profit & Loss | Revenue, COGS, Expenses, Net Profit | Per Branch / Consolidated |
| 7 | Cash Flow | Cash receipts, payments, balance | Per Branch / Consolidated |
| 8 | Shipping Report | Shipments by status, cost, city | Per Branch / Consolidated |
| 9 | Product Performance | Top selling, slow moving, profit margin | Per Branch / Consolidated |
| 10 | Customer Analysis | Customer spend, debt, lifetime value | Per Branch / Consolidated |
| 11 | Supplier Analysis | Supplier purchases, payments, balance | Per Branch / Consolidated |
| 12 | Comparative Report | Period comparison (30 days, 3 months, 1 year) | Per Branch / Consolidated |
| 13 | Financial Summary | Complete financial position | Per Branch / Consolidated |
| 14 | Branch Performance | Compare all branches side-by-side | Consolidated |
| 15 | Stock Transfer Report | All transfers between branches | Consolidated |

---

## Document Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Client Representative | | | |
| System Analyst | | | |
| AI Agent | | | |

---

**End of Document**

---

*This document serves as the complete plan for AI development. All requirements are specified in detail. The AI agent should implement the system following these requirements in the order of phases presented.*

---

**© 2026 - All Rights Reserved**

*This document is confidential and proprietary. Unauthorized use, disclosure, or distribution is prohibited.*