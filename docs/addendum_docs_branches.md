# Wholesale Shoe Store ERP System
## Application Requirements & Technical Specifications (Addendum) - With Multi-Branch Support

**Document Type:** Technical Requirements - Additional Sections  
**Version:** 2.0  
**Date:** June 2026  
**Purpose:** This document adds the missing sections to complete the full requirements specification with multi-branch support.

---

# Addendum: Missing Sections (With Multi-Branch Support)

---

## Section 2: Documentation Requirements

### 2.1 User Documentation

#### User Manual
- Complete guide for all system users
- Written in Arabic and English
- Step-by-step instructions with screenshots
- Role-specific guides:
  - Super Admin (System-wide management)
  - Branch Manager (Branch-level management)
  - Area Manager (Multi-branch oversight)
  - HQ Staff (Consolidated reporting)
  - Accountant (Branch financial management)
  - Warehouse Keeper (Branch inventory management)
  - Sales Staff (Branch sales processing)
- Branch management guide (creating, managing branches)
- Stock transfer guide (between branches)
- Consolidated reporting guide (HQ/Area Manager view)
- Frequently Asked Questions (FAQ) section

#### Admin Manual
- System setup guide
- Configuration instructions
- Tenant management guide
- **Branch management guide**
- User management guide (with branch assignment)
- Module management guide
- **Multi-branch settings and configuration**
- Customization guide
- Troubleshooting guide

#### Installation Guide
- Server requirements (detailed)
- Step-by-step installation instructions
- Configuration steps
- Common issues and solutions
- Post-installation checklist

#### API Documentation
- All API endpoints documented
- Authentication methods
- Request/response examples
- Error codes and messages
- Rate limits and throttling
- **Branch context in API requests**

### 2.2 Technical Documentation

#### Code Documentation
- PHPDoc blocks for all classes and methods
- README files for each module
- Database schema documentation (with branch tables)
- Architecture overview (with multi-branch design)

#### Developer Guide
- How to set up development environment
- Coding standards and conventions
- How to contribute
- How to test (including multi-branch testing)
- How to deploy

### 2.3 Documentation Management

#### Documentation Storage
| Document Type | Format | Location |
|---------------|--------|----------|
| User Manual | PDF, HTML | Help section in app + download |
| Admin Manual | PDF | Admin section |
| Installation Guide | PDF, Markdown | GitHub + download |
| API Documentation | HTML | /api/docs endpoint |
| Developer Guide | Markdown | GitHub repository |

#### Documentation Updates
- Update with every major release
- Version tracking for documentation
- Changelog maintained
- Release notes published

---

## Section 6: Rollback Plan

### 6.1 Rollback Procedures

#### Pre-Rollback Preparation
- Maintain full backup before any deployment
- Document current version number
- Notify users of potential downtime
- Prepare communication plan

#### Rollback Triggers
- Critical bugs discovered after deployment
- Performance degradation > 30%
- Security vulnerability found
- Database corruption
- Customer complaints > 5 per hour
- **Branch-specific data corruption**
- **Stock transfer failures**

#### Rollback Steps

**Step 1: Stop Application**
```bash
php artisan down
```

**Step 2: Restore Files**
- Revert to previous version from backup
- Restore previous .env file
- Restore previous configuration files

**Step 3: Restore Database**
- Restore database from pre-deployment backup
- Run rollback migrations if needed
- Verify data integrity
- **If branch-specific data corrupted: Restore branch data only from backup**

**Step 4: Clear Cache**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

**Step 5: Start Application**
```bash
php artisan up
```

**Step 6: Verify**
- Check application functionality
- Verify data integrity
- Test critical paths
- Monitor error logs
- **Verify branch data isolation**
- **Verify stock transfer functionality**

**Step 7: Communicate**
- Notify users of completion
- Document incident
- Create post-mortem report

### 6.2 Rollback Timeline

| Step | Time Estimate |
|------|---------------|
| Detection & Decision | 5-10 minutes |
| Preparation | 5 minutes |
| Stop Application | 1 minute |
| Restore Files | 10-15 minutes |
| Restore Database | 15-30 minutes |
| Clear Cache | 2 minutes |
| Start Application | 1 minute |
| Verification | 10 minutes |
| Communication | 5 minutes |
| **Total** | **~1 hour** |

### 6.3 Rollback Testing

| Test Type | Frequency |
|-----------|-----------|
| **Rollback Drill** | Quarterly |
| **Backup Restore Test** | Monthly |
| **Database Restore Test** | Monthly |
| **Full System Restore** | Quarterly |
| **Branch Data Restore Test** | Quarterly |
| **Stock Transfer Recovery Test** | Quarterly |

---

## Section 7: Compliance Requirements (Sudan-Specific)

### 7.1 Regulatory Compliance

#### Data Protection Laws
| Aspect | Requirement |
|--------|-------------|
| Data Storage | Within Sudan or approved cloud providers |
| Data Privacy | Compliant with Sudan data protection laws |
| Data Retention | Maximum 5 years per Sudanese law |
| Data Export | Subject to approval by authorities |
| Data Deletion | Upon business closure or legal request |
| **Branch Data Isolation** | Customer data separated by branch |

#### Business Compliance
| Aspect | Requirement |
|--------|-------------|
| Tax Compliance | VAT tracking for taxable items (per branch) |
| Invoice Legal Requirements | All mandatory invoice fields included (per branch) |
| Audit Trail | Minimum 5 years retention (per branch) |
| Transaction Logging | All transactions logged (per branch) |
| Reporting | Report to regulatory bodies as required (per branch) |
| **Branch Licensing** | Each branch must have valid business license |

### 7.2 Localization Requirements

#### Language (Already Covered)
- Arabic default (RTL support)
- English secondary (LTR support)

#### Date & Time
| Aspect | Format |
|--------|--------|
| Date Format | DD/MM/YYYY |
| Time Format | 24-hour (HH:MM) |
| Timezone | Africa/Khartoum |
| Week Start | Sunday (Islam) |
| Week End | Thursday |

#### Currency Requirements
| Aspect | Requirement |
|--------|-------------|
| Primary Currency | SDG (Sudanese Pound) |
| Secondary Currency | USD (US Dollar) |
| Exchange Rate | Manual update (as per market) |
| Currency Format | 1,234.56 SDG |
| Decimal Places | 2 |
| **Branch Currency** | Per branch currency settings (optional) |

#### Number Formatting
| Aspect | Format |
|--------|--------|
| Decimal Separator | . (dot) |
| Thousands Separator | , (comma) |
| Negative Numbers | -123.45 |

### 7.3 Islamic Finance Compliance

| Aspect | Requirement |
|--------|-------------|
| Interest/Riba | No interest-based transactions |
| Late Payment Fees | Not allowed (or as separate agreement) |
| Zakat Calculation | Optional feature for businesses (per branch) |
| Islamic Dates | Hijri calendar optional display |
| Business Hours | Friday as weekend (optional, per branch) |

### 7.4 Accessibility Requirements

| Aspect | Requirement |
|--------|-------------|
| WCAG 2.1 Level | AA minimum |
| Screen Reader Support | Full support |
| Keyboard Navigation | Complete navigation |
| Color Contrast | 4.5:1 for normal text |
| Font Size | Minimum 14px |
| Touch Targets | Minimum 44px |
| Focus Indicators | Visible focus states |
| ARIA Labels | All interactive elements |

---

## Section 8: Mobile Responsiveness

### 8.1 Responsive Design Requirements

#### Breakpoints
| Device Type | Screen Width | Layout |
|-------------|--------------|--------|
| **Mobile** | < 768px | Single column |
| **Tablet** | 768px - 1024px | 2 columns |
| **Desktop** | > 1024px | Full layout |
| **Large Desktop** | > 1440px | Expanded layout |

#### Mobile Optimizations

**Dashboard**
- KPI cards stacked vertically
- Charts simplified
- Quick actions prominent
- Swipe gestures for navigation
- **Branch selector accessible**

**Data Entry**
- Large touch targets
- Numeric keypad for numbers
- Auto-focus on first field
- Validation on blur

**Tables**
- Horizontal scroll for wide tables
- Show critical columns only
- Click row to expand details
- Collapsible filters
- **Branch column visible**

**Navigation**
- Bottom navigation bar (mobile)
- Hamburger menu
- Quick search accessible
- Back button support
- **Branch indicator visible**

### 8.2 Progressive Web App (PWA)

#### PWA Features
| Feature | Requirement |
|---------|-------------|
| **Service Worker** | ✓ Required |
| **Offline Support** | ✓ Required |
| **Push Notifications** | ✓ Required |
| **Add to Home Screen** | ✓ Required |
| **Splash Screen** | ✓ Recommended |
| **App Manifest** | ✓ Required |
| **Background Sync** | ✓ Recommended |

#### PWA Requirements
- Manifest file configured
- Service worker registered
- Caching strategy defined
- Offline fallback pages
- Sync capabilities
- Installation prompt
- **Branch context preserved offline**

### 8.3 Mobile Testing

| Test Type | Devices to Test |
|-----------|-----------------|
| Android | Chrome, Samsung Internet |
| iOS | Safari, Chrome |
| Tablets | iPad, Android tablets |
| Screen Sizes | Small, Medium, Large |

---

## Section 9: Accessibility (WCAG Compliance)

### 9.1 Level AA Requirements

#### Perceivable
| Requirement | Implementation |
|-------------|----------------|
| **Text Alternatives** | All non-text content has text alternative |
| **Captions** | All media has captions |
| **Adaptable** | Content can be presented in different ways |
| **Distinguishable** | Foreground/background contrast 4.5:1 |

#### Operable
| Requirement | Implementation |
|-------------|----------------|
| **Keyboard Accessible** | All functionality via keyboard |
| **Enough Time** | Time limits can be extended |
| **Seizures** | No flashing content > 3/sec |
| **Navigable** | Consistent navigation, skip links |

#### Understandable
| Requirement | Implementation |
|-------------|----------------|
| **Readable** | Language set, simplified text |
| **Predictable** | Consistent navigation and labels |
| **Input Assistance** | Error suggestions, labels |

#### Robust
| Requirement | Implementation |
|-------------|----------------|
| **Compatible** | Works with assistive technologies |
| **Parsable** | Valid HTML, ARIA attributes |

### 9.2 ARIA Implementation

| Element | ARIA Required |
|---------|---------------|
| **Modals** | role="dialog", aria-modal="true" |
| **Dropdowns** | role="listbox", aria-expanded |
| **Tabs** | role="tablist", role="tab" |
| **Tables** | role="table", role="row" |
| **Navigation** | role="navigation" |
| **Search** | role="search" |
| **Branch Selector** | role="combobox" |

### 9.3 Accessibility Testing

| Test Type | Frequency |
|-----------|-----------|
| **Automated Testing** | Every build |
| **Manual Testing** | Each sprint |
| **Screen Reader Testing** | Monthly |
| **User Testing** | Quarterly |
| **Compliance Audit** | Annually |

---

## Section 10: User Onboarding Flow

### 10.1 Onboarding Wizard

#### Welcome Screen
- Welcome message
- Language selection (Arabic/English)
- System tour invitation
- Quick start guide option

#### Business Setup
- Company name and details
- Business type selection
- Contact information
- Logo upload
- Theme color selection
- **Number of branches (initial setup)**

#### Branch Setup
- Add first branch:
  - Branch Name
  - Branch Address
  - Branch Phone
  - Branch Email
  - Branch Manager (optional)
- Add additional branches (optional)
- **Branch settings (currency, timezone, invoice prefix)**

#### System Configuration
- Default currency (SDG/USD)
- Exchange rate entry
- Date format selection
- Timezone (Africa/Khartoum default)
- Week start day (Sunday default)

#### Module Selection
- Core modules always enabled
- Optional module selection:
  - Supplier Management
  - Purchase Management
  - Shipping & Logistics
  - Financial Reports
  - Data Analysis
  - Multi-Branch Support (auto-enabled if multiple branches)

#### User Creation
- Create first Admin user (per branch)
- Add additional users (per branch)
- Role assignments (with branch scope)
- Invite users via email

#### Data Import
- Import products from CSV/Excel
- Import customers from CSV/Excel
- Import suppliers from CSV/Excel
- Import starting inventory (per branch)

#### Completion
- Summary of setup
- Access to tutorials
- Link to support
- Quick start button

### 10.2 First-Time User Guide

#### Tour Steps
1. **Dashboard:** Overview of KPIs and metrics (with branch context)
2. **Branch Selector:** How to switch between branches (if applicable)
3. **Products:** How to add and manage products
4. **Sales:** How to process a sale (per branch)
5. **Customers:** How to manage customers
6. **Stock Transfers:** How to transfer stock between branches
7. **Reports:** How to generate reports (per branch and consolidated)

#### Interactive Tutorials
| Topic | Duration | Type |
|-------|----------|------|
| Adding First Product | 3 minutes | Video |
| Processing First Sale | 4 minutes | Interactive |
| Recording First Expense | 2 minutes | Text + Images |
| Creating Customer | 3 minutes | Video |
| Generating Report | 2 minutes | Interactive |
| Transferring Stock Between Branches | 5 minutes | Video + Interactive |

### 10.3 Help Resources

#### In-App Help
- Context-sensitive help (question mark icons)
- Searchable knowledge base
- Tooltips for complex features
- Field-level help text
- **Branch-specific help content**

#### External Resources
- Video tutorials library
- PDF user manual
- PDF quick start guide
- WhatsApp/Telegram support group

#### Support Channels
| Channel | Response Time | Available |
|---------|---------------|-----------|
| In-App Support | 24 hours | 24/7 |
| Email Support | 12 hours | Business Hours |
| WhatsApp Support | 4 hours | Business Hours |
| Phone Support | Immediate | Business Hours |

### 10.4 User Training Materials

#### Training Content
| Format | Content | Target |
|--------|---------|--------|
| **Video Courses** | Full system training | All users |
| **Role Guides** | Role-specific tasks | Per role |
| **Branch Management Guide** | Managing multiple branches | Branch Managers, Area Managers, HQ Staff |
| **Cheat Sheets** | Quick reference | All users |
| **Workshops** | Live training sessions | All users |

---

## Section 12: Keyboard Shortcuts

### 12.1 Global Shortcuts

| Shortcut | Action | Platform |
|----------|--------|----------|
| `Ctrl + /` | Open help/search | All |
| `Ctrl + D` | Go to Dashboard | All |
| `Ctrl + S` | Save current item | All |
| `Esc` | Close modal/cancel | All |
| `Ctrl + N` | Create new item | All |
| `Ctrl + F` | Search/Filter | All |
| `Ctrl + P` | Print current view | All |
| `Ctrl + E` | Export current data | All |
| `Ctrl + R` | Refresh data | All |
| `Ctrl + B` | Switch branch (opens branch selector) | All |

### 12.2 Module Shortcuts

#### Products Module
| Shortcut | Action |
|----------|--------|
| `Ctrl + Shift + P` | Products list |
| `Ctrl + Shift + N` | New product |
| `Ctrl + Shift + S` | Search product |

#### Sales Module
| Shortcut | Action |
|----------|--------|
| `Ctrl + Shift + I` | New invoice |
| `Ctrl + Shift + C` | Customer search |
| `Ctrl + Shift + D` | Apply discount |

#### Inventory Module
| Shortcut | Action |
|----------|--------|
| `Ctrl + Shift + R` | Receive stock |
| `Ctrl + Shift + T` | Transfer stock |
| `Ctrl + Shift + L` | Low stock report |

#### Customers Module
| Shortcut | Action |
|----------|--------|
| `Ctrl + Shift + A` | Add customer |
| `Ctrl + Shift + Q` | Quick customer |
| `Ctrl + Shift + B` | Customer balance |

#### Branch Management Module
| Shortcut | Action |
|----------|--------|
| `Ctrl + Shift + B` | Switch branch |
| `Ctrl + Shift + M` | Manage branches |

### 12.3 Navigation Shortcuts

| Shortcut | Action |
|----------|--------|
| `Alt + 1` | Dashboard |
| `Alt + 2` | Sales |
| `Alt + 3` | Products |
| `Alt + 4` | Customers |
| `Alt + 5` | Inventory |
| `Alt + 6` | Purchases |
| `Alt + 7` | Expenses |
| `Alt + 8` | Reports |
| `Alt + 9` | Settings |
| `Alt + 0` | Branch Management |

### 12.4 Data Entry Shortcuts

| Shortcut | Action |
|----------|--------|
| `Tab` | Next field |
| `Shift + Tab` | Previous field |
| `Ctrl + Enter` | Submit form |
| `Ctrl + Z` | Undo |
| `Ctrl + Y` | Redo |
| `Ctrl + A` | Select all (in fields) |

---

## Section 13: Browser Extensions

### 13.1 Required Browser Extension Features

#### Chrome Extension (Recommended)
| Feature | Purpose |
|---------|---------|
| **Quick Invoice** | Create invoice without opening full app |
| **Barcode Scanner** | Scan product barcode to add to sale |
| **Stock Check** | Quick stock availability check (per branch) |
| **Customer Lookup** | Quick customer search and debt check |
| **Sales Summary** | Quick view of today's sales (per branch) |
| **Branch Switch** | Quick branch switching |

### 13.2 Extension Functionality

#### Quick Invoice
1. Click extension icon
2. Select branch (if multiple branches)
3. Search customer by name/phone
4. Scan/add products
5. Enter quantity
6. Apply discount (if any)
7. Complete sale
8. Print invoice directly

#### Barcode Scanner
1. Click extension icon
2. Click "Scan Barcode"
3. Scan product barcode
4. Product appears with details
5. Add to sale or check stock (per branch)

#### Stock Check
1. Click extension icon
2. Select branch (if multiple branches)
3. Enter product name/SKU/barcode
4. View current stock (per branch)
5. View low stock alerts (per branch)
6. View stock by location (per branch)

#### Customer Lookup
1. Click extension icon
2. Enter customer name/phone
3. View customer details:
   - Outstanding balance (per branch)
   - Consolidated balance (all branches)
   - Payment history
   - Recent purchases
4. Contact customer directly

#### Sales Summary
1. Click extension icon
2. Select branch (if multiple branches)
3. View today's metrics (per branch):
   - Number of sales
   - Total revenue
   - Average sale value
   - Top selling products
4. View weekly/monthly summary
5. View consolidated summary (if authorized)

### 13.3 Extension Installation

#### Chrome Web Store
1. Publish extension to Chrome Web Store
2. Provide direct download link
3. Auto-update support

#### Enterprise Deployment
1. Provide CRX file
2. Allow manual installation
3. Admin deployment options (group policy)

### 13.4 Extension Security

| Security Feature | Implementation |
|------------------|----------------|
| **Authentication** | API token or OAuth |
| **Data Encryption** | HTTPS only |
| **Local Storage** | Only non-sensitive data |
| **Permissions** | Minimal required |
| **Branch Context** | Token includes branch scope |

---

## Section 14: Multi-Branch Specific Requirements (New)

### 14.1 Branch Management Features

#### Branch Creation
- Create new branch with full details
- Branch-specific settings (currency, timezone, invoice prefix)
- Branch branding (logo, colors)
- Branch manager assignment
- Branch activation/deactivation

#### Branch Data Isolation
- Each branch has separate data for transactions
- Shared master data (products, customers, suppliers)
- Branch-specific reports and KPIs
- Consolidated reporting for HQ

#### Branch Settings
| Setting | Description | Scope |
|---------|-------------|-------|
| Branch Name | Display name | Branch |
| Branch Code | Unique identifier | Branch |
| Address | Physical address | Branch |
| Phone | Contact number | Branch |
| Email | Contact email | Branch |
| Logo | Branch branding | Branch |
| Colors | Primary/Secondary colors | Branch |
| Currency | Default currency | Branch |
| Timezone | Branch timezone | Branch |
| Invoice Prefix | Invoice number prefix | Branch |
| Language | Default language | Branch |
| Is Active | Branch status | Branch |

### 14.2 Stock Transfer Features

#### Transfer Request
- Request stock from another branch
- Select products and quantities
- Add notes/reason
- Track request status

#### Transfer Approval
- Approve or reject transfer requests
- Confirm quantities
- Set dispatch date
- Notify requesting branch

#### Transfer Tracking
- Status tracking (pending, approved, in-transit, received, cancelled)
- Transfer history
- Transfer reports

#### Transfer Reports
- Incoming transfers
- Outgoing transfers
- Transfer history
- Transfer summary

### 14.3 Consolidated Reporting

#### Reports Available
- Consolidated P&L
- Consolidated revenue
- Consolidated expenses
- Consolidated debt
- Consolidated inventory
- Consolidated sales
- Consolidated purchases

#### Branch Comparison Reports
- Side-by-side comparison
- Branch ranking
- Performance metrics
- Year-over-year comparison
- Month-over-month comparison

### 14.4 Multi-Branch Notifications

#### Notification Types
- Low stock alerts (per branch)
- Overdue debt alerts (per branch)
- Stock transfer requests
- Stock transfer approvals
- Stock transfer completions
- New branch creation
- Branch status changes

#### Notification Recipients
- Branch Manager (own branch only)
- Area Manager (all assigned branches)
- HQ Staff (all branches)
- Accountant (assigned branches)
- Warehouse Keeper (own branch only)

---

## Document Completion Summary

| Section | Status | Added |
|---------|--------|-------|
| 2. Documentation | ✅ Updated | ✓ (Multi-branch added) |
| 6. Rollback Plan | ✅ Updated | ✓ (Multi-branch added) |
| 7. Compliance (Sudan) | ✅ Updated | ✓ (Multi-branch added) |
| 8. Mobile Responsiveness | ✅ Updated | ✓ (Multi-branch added) |
| 9. Accessibility | ✅ Updated | ✓ (Multi-branch added) |
| 10. User Onboarding | ✅ Updated | ✓ (Multi-branch added) |
| 12. Keyboard Shortcuts | ✅ Updated | ✓ (Multi-branch added) |
| 13. Browser Extensions | ✅ Updated | ✓ (Multi-branch added) |
| 14. Multi-Branch Specific | ✅ New | ✓ (New section) |

---

**End of Addendum Document**

---

*This completes all missing sections with multi-branch support. The system requirements are now 100% complete and ready for development.*

---

**© 2026 - All Rights Reserved**