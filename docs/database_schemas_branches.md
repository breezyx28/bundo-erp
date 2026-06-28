# Wholesale Shoe Store ERP System
## Database Schema & Structure (With Multi-Branch Support)

**Document Type:** Database Architecture  
**Version:** 2.0  
**Date:** June 2026  
**Purpose:** This document outlines the complete database structure with best practices, optimization, and performance considerations for MySQL 8.0+.

---

## Database Design Principles

### Optimization Strategies Applied

| Strategy | Implementation |
|----------|---------------|
| **Indexing** | Strategic indexes on foreign keys and frequently queried columns |
| **Normalization** | 3NF with controlled denormalization for performance |
| **Partitioning** | Partition large tables by branch_id or date |
| **Data Types** | Appropriate smallest data types to reduce storage |
| **Foreign Keys** | Enforced referential integrity with cascading where appropriate |
| **Soft Deletes** | Using deleted_at for recoverable deletions |
| **JSON Fields** | For flexible/unstructured data |
| **Composite Indexes** | For multi-column queries |

---

## Table of Contents

1. [Core System Tables](#1-core-system-tables)
2. [User & Permission Tables](#2-user--permission-tables)
3. [Product & Inventory Tables](#3-product--inventory-tables)
4. [Customer & Sales Tables](#4-customer--sales-tables)
5. [Supplier & Purchase Tables](#5-supplier--purchase-tables)
6. [Financial Tables](#6-financial-tables)
7. [Shipping & Logistics Tables](#7-shipping--logistics-tables)
8. [Branch & Multi-Tenant Tables](#8-branch--multi-tenant-tables)
9. [System & Audit Tables](#9-system--audit-tables)
10. [Database Optimization Summary](#10-database-optimization-summary)

---

# 1. Core System Tables

## 1.1 tenants (Multi-Tenant Support)

**Purpose:** Stores tenant (business) information for white-label multi-tenant architecture.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Tenant/Business name |
| domain | VARCHAR(255) | NO | - | Unique domain/subdomain |
| database_name | VARCHAR(255) | YES | NULL | Separate database name (if used) |
| logo | VARCHAR(255) | YES | NULL | Logo file path |
| primary_color | VARCHAR(7) | YES | '#1a73e8' | Primary brand color |
| secondary_color | VARCHAR(7) | YES | '#ffffff' | Secondary brand color |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Suspended |
| settings | JSON | YES | NULL | JSON settings (locale, timezone, currency, etc.) |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_tenants_domain (domain)
- INDEX idx_tenants_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "name": "ABC Shoes Trading",
  "domain": "abcshoes.erp.com",
  "database_name": "tenant_abcshoes",
  "logo": "uploads/tenants/abc_logo.png",
  "primary_color": "#e74c3c",
  "secondary_color": "#ffffff",
  "is_active": 1,
  "settings": {
    "locale": "ar",
    "timezone": "Africa/Khartoum",
    "currency": "SDG",
    "currency_symbol": "SDG",
    "date_format": "DD/MM/YYYY"
  },
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 1.2 branches (Multi-Branch Support)

**Purpose:** Stores branch information for businesses with multiple locations.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| name | VARCHAR(255) | NO | - | Branch name |
| code | VARCHAR(50) | NO | - | Unique branch code |
| address | TEXT | YES | NULL | Physical address |
| phone | VARCHAR(20) | YES | NULL | Contact phone |
| email | VARCHAR(255) | YES | NULL | Contact email |
| manager_id | BIGINT UNSIGNED | YES | NULL | Branch manager user ID |
| logo | VARCHAR(255) | YES | NULL | Branch logo |
| primary_color | VARCHAR(7) | YES | '#1a73e8' | Branch primary color |
| secondary_color | VARCHAR(7) | YES | '#ffffff' | Branch secondary color |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Suspended |
| settings | JSON | YES | NULL | Branch-specific settings |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_branches_code_tenant (code, tenant_id)
- INDEX idx_branches_tenant_id (tenant_id)
- INDEX idx_branches_is_active (is_active)
- INDEX idx_branches_manager_id (manager_id)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "Atbara Main Branch",
  "code": "ATB01",
  "address": "123 Main Street, Atbara, Sudan",
  "phone": "+249912345678",
  "email": "atbara@abcshoes.com",
  "manager_id": 5,
  "logo": "uploads/branches/atbara_logo.png",
  "primary_color": "#2ecc71",
  "secondary_color": "#ffffff",
  "is_active": 1,
  "settings": {
    "currency": "SDG",
    "timezone": "Africa/Khartoum",
    "invoice_prefix": "ATB",
    "default_language": "ar"
  },
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

# 2. User & Permission Tables

## 2.1 users

**Purpose:** Stores system users with branch assignment.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| default_branch_id | BIGINT UNSIGNED | YES | NULL | Default branch for user |
| name | VARCHAR(255) | NO | - | Full name |
| email | VARCHAR(255) | NO | - | Email address |
| phone | VARCHAR(20) | YES | NULL | Phone number |
| password | VARCHAR(255) | NO | - | Hashed password |
| profile_photo | VARCHAR(255) | YES | NULL | Profile photo path |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| last_login_at | TIMESTAMP | YES | NULL | Last login timestamp |
| remember_token | VARCHAR(100) | YES | NULL | Remember me token |
| email_verified_at | TIMESTAMP | YES | NULL | Email verification timestamp |
| settings | JSON | YES | NULL | User preferences |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (default_branch_id) REFERENCES branches(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_users_email_tenant (email, tenant_id)
- INDEX idx_users_tenant_id (tenant_id)
- INDEX idx_users_default_branch_id (default_branch_id)
- INDEX idx_users_is_active (is_active)

**Example Data:**
```json
{
  "id": 5,
  "tenant_id": 1,
  "default_branch_id": 1,
  "name": "Ahmed Mohamed",
  "email": "ahmed@abcshoes.com",
  "phone": "+249912345678",
  "password": "$2y$10$encrypted_hash_here",
  "profile_photo": "uploads/users/ahmed.jpg",
  "is_active": 1,
  "last_login_at": "2026-06-01 09:00:00",
  "remember_token": "token_here",
  "email_verified_at": "2026-01-01 10:00:00",
  "settings": {
    "language": "ar",
    "theme": "light",
    "notification_preferences": {
      "email": true,
      "in_app": true,
      "whatsapp": false
    }
  },
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 2.2 roles (Spatie/laravel-permission)

**Purpose:** Stores user roles for permission management.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Role name |
| guard_name | VARCHAR(255) | NO | 'web' | Guard name |
| display_name | VARCHAR(255) | YES | NULL | Display name |
| description | TEXT | YES | NULL | Role description |
| is_system | TINYINT(1) | NO | 0 | 1=System role (cannot delete) |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_roles_name_guard (name, guard_name)

**Example Data:**
```json
{
  "id": 1,
  "name": "super-admin",
  "guard_name": "web",
  "display_name": "Super Admin",
  "description": "Full system access across all tenants and branches",
  "is_system": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-01-01 10:00:00"
}
```

---

## 2.3 permissions (Spatie/laravel-permission)

**Purpose:** Stores permissions for role-based access control.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Permission name |
| guard_name | VARCHAR(255) | NO | 'web' | Guard name |
| display_name | VARCHAR(255) | YES | NULL | Display name |
| description | TEXT | YES | NULL | Permission description |
| category | VARCHAR(100) | YES | NULL | Permission category |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_permissions_name_guard (name, guard_name)
- INDEX idx_permissions_category (category)

---

## 2.4 model_has_roles (Spatie/laravel-permission)

**Purpose:** Links users to roles.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| role_id | BIGINT UNSIGNED | NO | - | Foreign key to roles |
| model_type | VARCHAR(255) | NO | - | Model type (App\Models\User) |
| model_id | BIGINT UNSIGNED | NO | - | User ID |
| branch_id | BIGINT UNSIGNED | YES | NULL | Branch scope for role |

**Foreign Keys:**
- FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (role_id, model_id, model_type)
- INDEX idx_model_has_roles_model_id_type (model_id, model_type)
- INDEX idx_model_has_roles_branch_id (branch_id)

---

## 2.5 model_has_permissions (Spatie/laravel-permission)

**Purpose:** Links users directly to permissions.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| permission_id | BIGINT UNSIGNED | NO | - | Foreign key to permissions |
| model_type | VARCHAR(255) | NO | - | Model type (App\Models\User) |
| model_id | BIGINT UNSIGNED | NO | - | User ID |
| branch_id | BIGINT UNSIGNED | YES | NULL | Branch scope for permission |

**Foreign Keys:**
- FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (permission_id, model_id, model_type)
- INDEX idx_model_has_permissions_model_id_type (model_id, model_type)
- INDEX idx_model_has_permissions_branch_id (branch_id)

---

## 2.6 role_has_permissions (Spatie/laravel-permission)

**Purpose:** Links roles to permissions.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| permission_id | BIGINT UNSIGNED | NO | - | Foreign key to permissions |
| role_id | BIGINT UNSIGNED | NO | - | Foreign key to roles |

**Foreign Keys:**
- FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
- FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (permission_id, role_id)

---

## 2.7 branch_users

**Purpose:** Links users to branches with specific roles.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| user_id | BIGINT UNSIGNED | NO | - | Foreign key to users |
| role_id | BIGINT UNSIGNED | YES | NULL | Role at this branch |
| is_primary | TINYINT(1) | NO | 0 | 1=Primary branch assignment |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_branch_users_unique (branch_id, user_id)
- INDEX idx_branch_users_branch_id (branch_id)
- INDEX idx_branch_users_user_id (user_id)
- INDEX idx_branch_users_is_primary (is_primary)

**Example Data:**
```json
{
  "id": 1,
  "branch_id": 1,
  "user_id": 5,
  "role_id": 2,
  "is_primary": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-01-01 10:00:00"
}
```

---

# 3. Product & Inventory Tables

## 3.1 categories

**Purpose:** Product categories and sub-categories.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| parent_id | BIGINT UNSIGNED | YES | NULL | Self-referencing parent category |
| name | VARCHAR(100) | NO | - | Category name |
| slug | VARCHAR(100) | NO | - | URL-friendly name |
| description | TEXT | YES | NULL | Category description |
| image | VARCHAR(255) | YES | NULL | Category image |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| order | INT | NO | 0 | Display order |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_categories_slug_tenant (slug, tenant_id)
- INDEX idx_categories_tenant_id (tenant_id)
- INDEX idx_categories_parent_id (parent_id)
- INDEX idx_categories_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "parent_id": null,
  "name": "Sport Shoes",
  "slug": "sport-shoes",
  "description": "Athletic and sports footwear",
  "image": "uploads/categories/sport.jpg",
  "is_active": 1,
  "order": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 3.2 brands

**Purpose:** Product brands.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| name | VARCHAR(100) | NO | - | Brand name |
| slug | VARCHAR(100) | NO | - | URL-friendly name |
| description | TEXT | YES | NULL | Brand description |
| logo | VARCHAR(255) | YES | NULL | Brand logo |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_brands_slug_tenant (slug, tenant_id)
- INDEX idx_brands_tenant_id (tenant_id)
- INDEX idx_brands_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "Nike",
  "slug": "nike",
  "description": "Nike sports footwear and apparel",
  "logo": "uploads/brands/nike.png",
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 3.3 products

**Purpose:** Main product catalog (shared across branches).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| category_id | BIGINT UNSIGNED | YES | NULL | Foreign key to categories |
| brand_id | BIGINT UNSIGNED | YES | NULL | Foreign key to brands |
| name | VARCHAR(255) | NO | - | Product name |
| sku | VARCHAR(100) | NO | - | Stock Keeping Unit (unique) |
| barcode | VARCHAR(100) | YES | NULL | Barcode/QR code |
| description | TEXT | YES | NULL | Product description |
| purchase_price | DECIMAL(15,2) | NO | 0.00 | Purchase price in SDG |
| purchase_price_usd | DECIMAL(10,2) | NO | 0.00 | Purchase price in USD |
| selling_price | DECIMAL(15,2) | NO | 0.00 | Selling price in SDG |
| selling_price_usd | DECIMAL(10,2) | NO | 0.00 | Selling price in USD |
| reorder_level | INT | NO | 10 | Minimum stock alert level |
| min_order_qty | INT | NO | 1 | Minimum order quantity |
| unit | VARCHAR(20) | YES | 'pair' | Unit of measurement |
| weight | DECIMAL(10,2) | YES | NULL | Weight in kg |
| dimensions | VARCHAR(100) | YES | NULL | Dimensions (LxWxH) |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
- FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_products_sku_tenant (sku, tenant_id)
- INDEX idx_products_tenant_id (tenant_id)
- INDEX idx_products_category_id (category_id)
- INDEX idx_products_brand_id (brand_id)
- INDEX idx_products_name (name)
- INDEX idx_products_barcode (barcode)
- INDEX idx_products_is_active (is_active)
- FULLTEXT INDEX ft_products_name (name)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "category_id": 1,
  "brand_id": 1,
  "name": "Nike Air Max 270",
  "sku": "NIK-AM270-01",
  "barcode": "8901234567890",
  "description": "Nike Air Max 270 with responsive cushioning",
  "purchase_price": 1320.00,
  "purchase_price_usd": 290.00,
  "selling_price": 1650.00,
  "selling_price_usd": 360.00,
  "reorder_level": 20,
  "min_order_qty": 10,
  "unit": "pair",
  "weight": 0.45,
  "dimensions": "30x20x12",
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 3.4 product_variants

**Purpose:** Product variants (size, color, etc.).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| size | VARCHAR(20) | YES | NULL | Size (EU, US, UK) |
| color | VARCHAR(50) | YES | NULL | Color name |
| sku | VARCHAR(100) | YES | NULL | Variant SKU |
| barcode | VARCHAR(100) | YES | NULL | Variant barcode |
| additional_info | JSON | YES | NULL | Additional variant data |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_product_variants_sku (sku)
- INDEX idx_product_variants_product_id (product_id)
- INDEX idx_product_variants_size (size)
- INDEX idx_product_variants_color (color)

**Example Data:**
```json
{
  "id": 1,
  "product_id": 1,
  "size": "42",
  "color": "Black",
  "sku": "NIK-AM270-01-42-BLK",
  "barcode": "8901234567891",
  "additional_info": {
    "season": "Summer",
    "material": "Mesh",
    "gender": "Men"
  },
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00"
}
```

---

## 3.5 product_images

**Purpose:** Product images.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| image_path | VARCHAR(255) | NO | - | Image file path |
| is_primary | TINYINT(1) | NO | 0 | 1=Primary image |
| order | INT | NO | 0 | Display order |
| alt_text | VARCHAR(255) | YES | NULL | Image alt text |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_product_images_product_id (product_id)
- INDEX idx_product_images_is_primary (is_primary)

---

## 3.6 product_batches

**Purpose:** Product inventory batches (branch-specific stock tracking).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| variant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to product_variants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| supplier_id | BIGINT UNSIGNED | YES | NULL | Foreign key to suppliers |
| purchase_order_id | BIGINT UNSIGNED | YES | NULL | Foreign key to purchase_orders |
| batch_number | VARCHAR(100) | NO | - | Unique batch identifier |
| quantity | INT | NO | 0 | Initial batch quantity |
| remaining_quantity | INT | NO | 0 | Current available quantity |
| cost_per_unit | DECIMAL(15,2) | NO | 0.00 | Cost per unit in SDG |
| cost_per_unit_usd | DECIMAL(10,2) | NO | 0.00 | Cost per unit in USD |
| selling_price_at_purchase | DECIMAL(15,2) | YES | NULL | Selling price at purchase time |
| purchase_date | DATE | NO | - | Date of purchase |
| expiry_date | DATE | YES | NULL | Expiry date (if applicable) |
| location_id | BIGINT UNSIGNED | YES | NULL | Storage location |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Depleted |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
- FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
- FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_product_batches_batch_number (batch_number)
- INDEX idx_product_batches_product_id (product_id)
- INDEX idx_product_batches_variant_id (variant_id)
- INDEX idx_product_batches_branch_id (branch_id)
- INDEX idx_product_batches_supplier_id (supplier_id)
- INDEX idx_product_batches_purchase_date (purchase_date)
- INDEX idx_product_batches_expiry_date (expiry_date)
- INDEX idx_product_batches_remaining_qty (remaining_quantity)
- INDEX idx_product_batches_is_active (is_active)
- COMPOSITE INDEX idx_product_batches_product_branch (product_id, branch_id)

**Example Data:**
```json
{
  "id": 1,
  "product_id": 1,
  "variant_id": 1,
  "branch_id": 1,
  "supplier_id": 1,
  "purchase_order_id": 1,
  "batch_number": "BATCH-2026-001",
  "quantity": 100,
  "remaining_quantity": 75,
  "cost_per_unit": 1320.00,
  "cost_per_unit_usd": 290.00,
  "selling_price_at_purchase": 1650.00,
  "purchase_date": "2026-01-15",
  "expiry_date": null,
  "location_id": 1,
  "is_active": 1,
  "created_at": "2026-01-15 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 3.7 inventory_locations

**Purpose:** Storage locations within branches.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| name | VARCHAR(100) | NO | - | Location name |
| code | VARCHAR(50) | NO | - | Location code |
| description | TEXT | YES | NULL | Location description |
| is_default | TINYINT(1) | NO | 0 | 1=Default location |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_inventory_locations_code_branch (code, branch_id)
- INDEX idx_inventory_locations_branch_id (branch_id)
- INDEX idx_inventory_locations_is_default (is_default)

**Example Data:**
```json
{
  "id": 1,
  "branch_id": 1,
  "name": "Main Warehouse",
  "code": "WH-ATB-01",
  "description": "Main storage warehouse for Atbara branch",
  "is_default": 1,
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-01-01 10:00:00",
  "deleted_at": null
}
```

---

## 3.8 inventory_transfers

**Purpose:** Stock transfers between branches.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| from_branch_id | BIGINT UNSIGNED | NO | - | Source branch |
| to_branch_id | BIGINT UNSIGNED | NO | - | Destination branch |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| variant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to product_variants |
| batch_id | BIGINT UNSIGNED | NO | - | Foreign key to product_batches |
| quantity | INT | NO | - | Transfer quantity |
| transfer_number | VARCHAR(100) | NO | - | Unique transfer number |
| status | ENUM('pending','approved','in_transit','received','cancelled') | NO | 'pending' | Transfer status |
| request_date | DATE | NO | - | Date of request |
| dispatch_date | DATE | YES | NULL | Date dispatched |
| received_date | DATE | YES | NULL | Date received |
| reason | TEXT | YES | NULL | Transfer reason |
| notes | TEXT | YES | NULL | Additional notes |
| requested_by | BIGINT UNSIGNED | NO | - | User who requested |
| approved_by | BIGINT UNSIGNED | YES | NULL | User who approved |
| dispatched_by | BIGINT UNSIGNED | YES | NULL | User who dispatched |
| received_by | BIGINT UNSIGNED | YES | NULL | User who received |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (from_branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (to_branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
- FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
- FOREIGN KEY (batch_id) REFERENCES product_batches(id) ON DELETE CASCADE
- FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE
- FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
- FOREIGN KEY (dispatched_by) REFERENCES users(id) ON DELETE SET NULL
- FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_inventory_transfers_number (transfer_number)
- INDEX idx_inventory_transfers_tenant_id (tenant_id)
- INDEX idx_inventory_transfers_from_branch (from_branch_id)
- INDEX idx_inventory_transfers_to_branch (to_branch_id)
- INDEX idx_inventory_transfers_product_id (product_id)
- INDEX idx_inventory_transfers_status (status)
- INDEX idx_inventory_transfers_request_date (request_date)
- COMPOSITE INDEX idx_transfers_from_to (from_branch_id, to_branch_id, status)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "from_branch_id": 1,
  "to_branch_id": 2,
  "product_id": 1,
  "variant_id": 1,
  "batch_id": 1,
  "quantity": 50,
  "transfer_number": "TRF-2026-0001",
  "status": "received",
  "request_date": "2026-05-20",
  "dispatch_date": "2026-05-22",
  "received_date": "2026-05-25",
  "reason": "Stock transfer to Madani branch",
  "notes": "Requested due to high demand in Madani",
  "requested_by": 5,
  "approved_by": 6,
  "dispatched_by": 7,
  "received_by": 8,
  "created_at": "2026-05-20 10:00:00",
  "updated_at": "2026-05-25 14:00:00"
}
```

---

# 4. Customer & Sales Tables

## 4.1 customers

**Purpose:** Customer database (shared across branches).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| default_branch_id | BIGINT UNSIGNED | YES | NULL | Preferred branch |
| name | VARCHAR(255) | NO | - | Customer name |
| phone | VARCHAR(20) | NO | - | Phone number |
| email | VARCHAR(255) | YES | NULL | Email address |
| whatsapp | VARCHAR(20) | YES | NULL | WhatsApp number |
| address | TEXT | YES | NULL | Physical address |
| city | VARCHAR(100) | YES | NULL | City |
| is_quick | TINYINT(1) | NO | 0 | 1=Quick customer |
| credit_limit | DECIMAL(15,2) | NO | 0.00 | Credit limit |
| badge | VARCHAR(50) | YES | NULL | Customer badge (Gold, Silver, Bronze) |
| notes | TEXT | YES | NULL | Additional notes |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (default_branch_id) REFERENCES branches(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_customers_tenant_id (tenant_id)
- INDEX idx_customers_name (name)
- INDEX idx_customers_phone (phone)
- INDEX idx_customers_email (email)
- INDEX idx_customers_default_branch (default_branch_id)
- INDEX idx_customers_is_quick (is_quick)
- INDEX idx_customers_is_active (is_active)
- COMPOSITE INDEX idx_customers_name_phone (name, phone)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "default_branch_id": 1,
  "name": "Mohammed Ali",
  "phone": "+249912345678",
  "email": "mohammed@example.com",
  "whatsapp": "+249912345678",
  "address": "123 Main St, Khartoum",
  "city": "Khartoum",
  "is_quick": 0,
  "credit_limit": 50000.00,
  "badge": "Gold",
  "notes": "VIP customer, discount policy applies",
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 4.2 customer_branch_balances

**Purpose:** Customer outstanding balance per branch.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| customer_id | BIGINT UNSIGNED | NO | - | Foreign key to customers |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| total_debt | DECIMAL(15,2) | NO | 0.00 | Total outstanding debt |
| current_balance | DECIMAL(15,2) | NO | 0.00 | Current balance |
| overdue_amount | DECIMAL(15,2) | NO | 0.00 | Overdue amount |
| total_purchases | DECIMAL(15,2) | NO | 0.00 | Total purchases |
| total_payments | DECIMAL(15,2) | NO | 0.00 | Total payments |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_customer_branch_balances_unique (customer_id, branch_id)
- INDEX idx_customer_branch_balances_branch_id (branch_id)

**Example Data:**
```json
{
  "id": 1,
  "customer_id": 1,
  "branch_id": 1,
  "total_debt": 16500.00,
  "current_balance": 16500.00,
  "overdue_amount": 0.00,
  "total_purchases": 66000.00,
  "total_payments": 49500.00,
  "updated_at": "2026-06-01 15:30:00"
}
```

---

## 4.3 sales_invoices

**Purpose:** Sales invoices (branch-specific).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| customer_id | BIGINT UNSIGNED | YES | NULL | Foreign key to customers |
| invoice_number | VARCHAR(100) | NO | - | Unique invoice number |
| invoice_date | DATE | NO | - | Invoice date |
| due_date | DATE | YES | NULL | Payment due date |
| total_amount | DECIMAL(15,2) | NO | 0.00 | Total amount in SDG |
| total_amount_usd | DECIMAL(10,2) | NO | 0.00 | Total amount in USD |
| discount_type | ENUM('percentage','fixed') | YES | NULL | Discount type |
| discount_value | DECIMAL(15,2) | NO | 0.00 | Discount value |
| discount_amount | DECIMAL(15,2) | NO | 0.00 | Discount amount |
| net_amount | DECIMAL(15,2) | NO | 0.00 | Net amount in SDG |
| net_amount_usd | DECIMAL(10,2) | NO | 0.00 | Net amount in USD |
| paid_amount | DECIMAL(15,2) | NO | 0.00 | Paid amount in SDG |
| balance | DECIMAL(15,2) | NO | 0.00 | Outstanding balance |
| payment_status | ENUM('paid','partial','unpaid') | NO | 'unpaid' | Payment status |
| payment_method | ENUM('cash','bank_transfer','check','mobile_money') | YES | NULL | Payment method |
| transaction_number | VARCHAR(100) | YES | NULL | Transaction reference |
| exchange_rate | DECIMAL(10,2) | NO | 0.00 | Exchange rate at sale time |
| notes | TEXT | YES | NULL | Invoice notes |
| created_by | BIGINT UNSIGNED | NO | - | User who created |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
- FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_sales_invoices_number_tenant (invoice_number, tenant_id)
- INDEX idx_sales_invoices_tenant_id (tenant_id)
- INDEX idx_sales_invoices_branch_id (branch_id)
- INDEX idx_sales_invoices_customer_id (customer_id)
- INDEX idx_sales_invoices_invoice_date (invoice_date)
- INDEX idx_sales_invoices_due_date (due_date)
- INDEX idx_sales_invoices_payment_status (payment_status)
- INDEX idx_sales_invoices_created_by (created_by)
- COMPOSITE INDEX idx_sales_invoices_customer_branch (customer_id, branch_id)
- COMPOSITE INDEX idx_sales_invoices_date_status (invoice_date, payment_status)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "customer_id": 1,
  "invoice_number": "INV-ATB-2026-0001",
  "invoice_date": "2026-01-15",
  "due_date": "2026-02-14",
  "total_amount": 33000.00,
  "total_amount_usd": 7200.00,
  "discount_type": "percentage",
  "discount_value": 10.00,
  "discount_amount": 3300.00,
  "net_amount": 29700.00,
  "net_amount_usd": 6480.00,
  "paid_amount": 16500.00,
  "balance": 13200.00,
  "payment_status": "partial",
  "payment_method": "cash",
  "transaction_number": null,
  "exchange_rate": 458.33,
  "notes": "First order of the year",
  "created_by": 5,
  "created_at": "2026-01-15 10:00:00",
  "updated_at": "2026-01-15 10:00:00",
  "deleted_at": null
}
```

---

## 4.4 sales_invoice_items

**Purpose:** Items within sales invoices.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| sales_invoice_id | BIGINT UNSIGNED | NO | - | Foreign key to sales_invoices |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| variant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to product_variants |
| batch_id | BIGINT UNSIGNED | NO | - | Foreign key to product_batches |
| quantity | INT | NO | - | Quantity sold |
| unit_price | DECIMAL(15,2) | NO | 0.00 | Unit price in SDG |
| unit_price_usd | DECIMAL(10,2) | NO | 0.00 | Unit price in USD |
| cost_per_unit | DECIMAL(15,2) | NO | 0.00 | COGS from batch |
| discount_type | ENUM('percentage','fixed') | YES | NULL | Discount type |
| discount_value | DECIMAL(15,2) | NO | 0.00 | Discount value |
| total | DECIMAL(15,2) | NO | 0.00 | Total in SDG |
| total_usd | DECIMAL(10,2) | NO | 0.00 | Total in USD |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
- FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
- FOREIGN KEY (batch_id) REFERENCES product_batches(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_sales_invoice_items_invoice_id (sales_invoice_id)
- INDEX idx_sales_invoice_items_product_id (product_id)
- INDEX idx_sales_invoice_items_variant_id (variant_id)
- INDEX idx_sales_invoice_items_batch_id (batch_id)

**Example Data:**
```json
{
  "id": 1,
  "sales_invoice_id": 1,
  "product_id": 1,
  "variant_id": 1,
  "batch_id": 1,
  "quantity": 10,
  "unit_price": 1650.00,
  "unit_price_usd": 360.00,
  "cost_per_unit": 1320.00,
  "discount_type": null,
  "discount_value": 0.00,
  "total": 16500.00,
  "total_usd": 3600.00,
  "created_at": "2026-01-15 10:00:00",
  "updated_at": "2026-01-15 10:00:00"
}
```

---

# 5. Supplier & Purchase Tables

## 5.1 suppliers

**Purpose:** Supplier database (shared across branches).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| name | VARCHAR(255) | NO | - | Supplier name |
| phone | VARCHAR(20) | YES | NULL | Phone number |
| email | VARCHAR(255) | YES | NULL | Email address |
| address | TEXT | YES | NULL | Physical address |
| contact_person | VARCHAR(255) | YES | NULL | Contact person name |
| payment_terms | VARCHAR(100) | YES | NULL | Payment terms |
| rating | TINYINT | NO | 0 | Rating (1-5) |
| notes | TEXT | YES | NULL | Additional notes |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_suppliers_tenant_id (tenant_id)
- INDEX idx_suppliers_name (name)
- INDEX idx_suppliers_phone (phone)
- INDEX idx_suppliers_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "Global Shoe Distributors",
  "phone": "+249911234567",
  "email": "info@globalshoe.com",
  "address": "Industrial Area, Khartoum",
  "contact_person": "Mr. Karim",
  "payment_terms": "Net 30",
  "rating": 4,
  "notes": "Reliable supplier, good quality",
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 5.2 purchase_orders

**Purpose:** Purchase orders (branch-specific).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| supplier_id | BIGINT UNSIGNED | NO | - | Foreign key to suppliers |
| po_number | VARCHAR(100) | NO | - | Unique purchase order number |
| order_date | DATE | NO | - | Order date |
| expected_delivery_date | DATE | YES | NULL | Expected delivery date |
| total_amount | DECIMAL(15,2) | NO | 0.00 | Total amount in SDG |
| total_amount_usd | DECIMAL(10,2) | NO | 0.00 | Total amount in USD |
| paid_amount | DECIMAL(15,2) | NO | 0.00 | Paid amount in SDG |
| payment_status | ENUM('unpaid','partial','paid') | NO | 'unpaid' | Payment status |
| order_status | ENUM('draft','ordered','received','partial','cancelled') | NO | 'draft' | Order status |
| notes | TEXT | YES | NULL | Order notes |
| created_by | BIGINT UNSIGNED | NO | - | User who created |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
- FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_purchase_orders_number_tenant (po_number, tenant_id)
- INDEX idx_purchase_orders_tenant_id (tenant_id)
- INDEX idx_purchase_orders_branch_id (branch_id)
- INDEX idx_purchase_orders_supplier_id (supplier_id)
- INDEX idx_purchase_orders_order_date (order_date)
- INDEX idx_purchase_orders_payment_status (payment_status)
- INDEX idx_purchase_orders_order_status (order_status)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "supplier_id": 1,
  "po_number": "PO-ATB-2026-0001",
  "order_date": "2026-01-10",
  "expected_delivery_date": "2026-01-25",
  "total_amount": 132000.00,
  "total_amount_usd": 29000.00,
  "paid_amount": 66000.00,
  "payment_status": "partial",
  "order_status": "received",
  "notes": "First order of the year",
  "created_by": 5,
  "created_at": "2026-01-10 10:00:00",
  "updated_at": "2026-01-25 15:30:00",
  "deleted_at": null
}
```

---

## 5.3 purchase_order_items

**Purpose:** Items within purchase orders.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| purchase_order_id | BIGINT UNSIGNED | NO | - | Foreign key to purchase_orders |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| variant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to product_variants |
| quantity | INT | NO | - | Order quantity |
| received_quantity | INT | NO | 0 | Received quantity |
| cost_per_unit | DECIMAL(15,2) | NO | 0.00 | Cost per unit in SDG |
| cost_per_unit_usd | DECIMAL(10,2) | NO | 0.00 | Cost per unit in USD |
| total | DECIMAL(15,2) | NO | 0.00 | Total in SDG |
| total_usd | DECIMAL(10,2) | NO | 0.00 | Total in USD |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
- FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_purchase_order_items_order_id (purchase_order_id)
- INDEX idx_purchase_order_items_product_id (product_id)
- INDEX idx_purchase_order_items_variant_id (variant_id)

**Example Data:**
```json
{
  "id": 1,
  "purchase_order_id": 1,
  "product_id": 1,
  "variant_id": 1,
  "quantity": 100,
  "received_quantity": 100,
  "cost_per_unit": 1320.00,
  "cost_per_unit_usd": 290.00,
  "total": 132000.00,
  "total_usd": 29000.00,
  "created_at": "2026-01-10 10:00:00",
  "updated_at": "2026-01-25 15:30:00"
}
```

---

# 6. Financial Tables

## 6.1 payments

**Purpose:** Payment recording (customer and supplier).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| customer_id | BIGINT UNSIGNED | YES | NULL | Foreign key to customers |
| supplier_id | BIGINT UNSIGNED | YES | NULL | Foreign key to suppliers |
| sales_invoice_id | BIGINT UNSIGNED | YES | NULL | Foreign key to sales_invoices |
| purchase_order_id | BIGINT UNSIGNED | YES | NULL | Foreign key to purchase_orders |
| amount | DECIMAL(15,2) | NO | 0.00 | Amount in SDG |
| amount_usd | DECIMAL(10,2) | YES | NULL | Amount in USD |
| payment_method | ENUM('cash','bank_transfer','check','mobile_money') | NO | - | Payment method |
| transaction_number | VARCHAR(100) | YES | NULL | Transaction reference |
| reference_number | VARCHAR(100) | YES | NULL | Reference number |
| payment_date | DATE | NO | - | Payment date |
| notes | TEXT | YES | NULL | Payment notes |
| recorded_by | BIGINT UNSIGNED | NO | - | User who recorded |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
- FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
- FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE SET NULL
- FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL
- FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_payments_tenant_id (tenant_id)
- INDEX idx_payments_branch_id (branch_id)
- INDEX idx_payments_customer_id (customer_id)
- INDEX idx_payments_supplier_id (supplier_id)
- INDEX idx_payments_sales_invoice_id (sales_invoice_id)
- INDEX idx_payments_purchase_order_id (purchase_order_id)
- INDEX idx_payments_payment_date (payment_date)
- INDEX idx_payments_transaction_number (transaction_number)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "customer_id": 1,
  "supplier_id": null,
  "sales_invoice_id": 1,
  "purchase_order_id": null,
  "amount": 16500.00,
  "amount_usd": 3600.00,
  "payment_method": "cash",
  "transaction_number": null,
  "reference_number": null,
  "payment_date": "2026-01-15",
  "notes": "Partial payment",
  "recorded_by": 5,
  "created_at": "2026-01-15 10:00:00",
  "updated_at": "2026-01-15 10:00:00"
}
```

---

## 6.2 expenses

**Purpose:** Expense tracking (branch-specific).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| expense_category_id | BIGINT UNSIGNED | NO | - | Foreign key to expense_categories |
| reference_id | BIGINT UNSIGNED | YES | NULL | Reference entity ID |
| reference_type | VARCHAR(100) | YES | NULL | Reference entity type |
| amount | DECIMAL(15,2) | NO | 0.00 | Amount in SDG |
| amount_usd | DECIMAL(10,2) | YES | NULL | Amount in USD |
| description | TEXT | NO | - | Expense description |
| expense_date | DATE | NO | - | Expense date |
| payment_method | ENUM('cash','bank_transfer','check') | NO | 'cash' | Payment method |
| receipt_number | VARCHAR(100) | YES | NULL | Receipt number |
| receipt_image | VARCHAR(255) | YES | NULL | Receipt image path |
| recorded_by | BIGINT UNSIGNED | NO | - | User who recorded |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE CASCADE
- FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_expenses_tenant_id (tenant_id)
- INDEX idx_expenses_branch_id (branch_id)
- INDEX idx_expenses_category_id (expense_category_id)
- INDEX idx_expenses_expense_date (expense_date)
- INDEX idx_expenses_reference (reference_id, reference_type)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "expense_category_id": 1,
  "reference_id": 1,
  "reference_type": "purchase_order",
  "amount": 5000.00,
  "amount_usd": 1091.00,
  "description": "Shipping cost for purchase order",
  "expense_date": "2026-01-20",
  "payment_method": "cash",
  "receipt_number": "REC-001",
  "receipt_image": "uploads/expenses/receipt_001.jpg",
  "recorded_by": 5,
  "created_at": "2026-01-20 10:00:00",
  "updated_at": "2026-01-20 10:00:00",
  "deleted_at": null
}
```

---

## 6.3 expense_categories

**Purpose:** Expense categories.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| name | VARCHAR(100) | NO | - | Category name |
| description | TEXT | YES | NULL | Category description |
| is_operational | TINYINT(1) | NO | 1 | 1=Operational, 0=Non-operational |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_expense_categories_tenant_id (tenant_id)
- INDEX idx_expense_categories_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "Shipping & Logistics",
  "description": "Shipping costs, courier services",
  "is_operational": 1,
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-01-01 10:00:00",
  "deleted_at": null
}
```

---

# 7. Shipping & Logistics Tables

## 7.1 logistics_companies

**Purpose:** Logistics/Shipping companies (branch-specific optional).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | YES | NULL | Foreign key to branches |
| name | VARCHAR(255) | NO | - | Company name |
| phone | VARCHAR(20) | YES | NULL | Phone number |
| email | VARCHAR(255) | YES | NULL | Email address |
| address | TEXT | YES | NULL | Physical address |
| contact_person | VARCHAR(255) | YES | NULL | Contact person |
| rating | TINYINT | NO | 0 | Rating (1-5) |
| notes | TEXT | YES | NULL | Additional notes |
| is_active | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_logistics_tenant_id (tenant_id)
- INDEX idx_logistics_branch_id (branch_id)
- INDEX idx_logistics_name (name)
- INDEX idx_logistics_is_active (is_active)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "name": "Sudan Express Logistics",
  "phone": "+249915678901",
  "email": "info@sudanexpress.com",
  "address": "Logistics Hub, Khartoum",
  "contact_person": "Mr. Hassan",
  "rating": 4,
  "notes": "Reliable logistics partner",
  "is_active": 1,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-06-01 15:30:00",
  "deleted_at": null
}
```

---

## 7.2 shipments

**Purpose:** Customer shipments (branch-specific).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| sales_invoice_id | BIGINT UNSIGNED | NO | - | Foreign key to sales_invoices |
| customer_id | BIGINT UNSIGNED | NO | - | Foreign key to customers |
| logistics_company_id | BIGINT UNSIGNED | NO | - | Foreign key to logistics_companies |
| tracking_number | VARCHAR(100) | YES | NULL | Tracking number |
| waybill_number | VARCHAR(100) | YES | NULL | Waybill number |
| dispatch_city | VARCHAR(100) | NO | - | Dispatch city |
| delivery_city | VARCHAR(100) | NO | - | Delivery city |
| number_of_boxes | INT | NO | 0 | Number of boxes/cartons |
| shipment_value | DECIMAL(15,2) | NO | 0.00 | Shipment value in SDG |
| shipping_cost | DECIMAL(15,2) | NO | 0.00 | Shipping cost in SDG |
| shipping_cost_usd | DECIMAL(10,2) | YES | NULL | Shipping cost in USD |
| status | ENUM('pending','processing','handed_to_logistics','in_transit','arrived','delivered','returned') | NO | 'pending' | Shipment status |
| pod_image | VARCHAR(255) | YES | NULL | Proof of delivery image |
| notes | TEXT | YES | NULL | Additional notes |
| dispatched_at | TIMESTAMP | YES | NULL | Dispatch timestamp |
| delivered_at | TIMESTAMP | YES | NULL | Delivery timestamp |
| created_by | BIGINT UNSIGNED | NO | - | User who created |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE
- FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
- FOREIGN KEY (logistics_company_id) REFERENCES logistics_companies(id) ON DELETE CASCADE
- FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_shipments_tenant_id (tenant_id)
- INDEX idx_shipments_branch_id (branch_id)
- INDEX idx_shipments_sales_invoice_id (sales_invoice_id)
- INDEX idx_shipments_customer_id (customer_id)
- INDEX idx_shipments_logistics_company (logistics_company_id)
- INDEX idx_shipments_tracking_number (tracking_number)
- INDEX idx_shipments_status (status)
- INDEX idx_shipments_dispatch_city (dispatch_city)
- INDEX idx_shipments_delivery_city (delivery_city)

**Example Data:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "branch_id": 1,
  "sales_invoice_id": 1,
  "customer_id": 1,
  "logistics_company_id": 1,
  "tracking_number": "SE-2026-001",
  "waybill_number": "WB-2026-001",
  "dispatch_city": "Atbara",
  "delivery_city": "Madani",
  "number_of_boxes": 5,
  "shipment_value": 29700.00,
  "shipping_cost": 5000.00,
  "shipping_cost_usd": 1091.00,
  "status": "delivered",
  "pod_image": "uploads/shipments/pod_001.jpg",
  "notes": "Priority shipping",
  "dispatched_at": "2026-01-16 09:00:00",
  "delivered_at": "2026-01-18 14:00:00",
  "created_by": 5,
  "created_at": "2026-01-15 10:00:00",
  "updated_at": "2026-01-18 14:00:00",
  "deleted_at": null
}
```

---

## 7.3 shipment_returns

**Purpose:** Shipment returns/adjustments.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | NO | - | Foreign key to branches |
| shipment_id | BIGINT UNSIGNED | NO | - | Foreign key to shipments |
| product_id | BIGINT UNSIGNED | NO | - | Foreign key to products |
| variant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to product_variants |
| batch_id | BIGINT UNSIGNED | NO | - | Foreign key to product_batches |
| quantity | INT | NO | - | Return quantity |
| reason | TEXT | NO | - | Return reason |
| status | ENUM('pending','approved','rejected','processed') | NO | 'pending' | Return status |
| processed_by | BIGINT UNSIGNED | YES | NULL | User who processed |
| processed_at | TIMESTAMP | YES | NULL | Processing timestamp |
| created_by | BIGINT UNSIGNED | NO | - | User who created |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
- FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
- FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
- FOREIGN KEY (batch_id) REFERENCES product_batches(id) ON DELETE CASCADE
- FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
- FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_shipment_returns_tenant_id (tenant_id)
- INDEX idx_shipment_returns_branch_id (branch_id)
- INDEX idx_shipment_returns_shipment_id (shipment_id)
- INDEX idx_shipment_returns_status (status)

---

# 8. System & Audit Tables

## 8.1 modules

**Purpose:** System modules for feature management.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NO | - | Module name |
| key | VARCHAR(50) | NO | - | Unique module key |
| description | TEXT | YES | NULL | Module description |
| is_core | TINYINT(1) | NO | 0 | 1=Core module (always enabled) |
| is_enabled | TINYINT(1) | NO | 1 | 1=Enabled by default |
| order | INT | NO | 0 | Display order |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_modules_key (key)
- INDEX idx_modules_is_enabled (is_enabled)

**Example Data:**
```json
{
  "id": 1,
  "name": "Multi-Branch",
  "key": "multi_branch",
  "description": "Enable multiple branch support",
  "is_core": 0,
  "is_enabled": 1,
  "order": 10,
  "created_at": "2026-01-01 10:00:00",
  "updated_at": "2026-01-01 10:00:00"
}
```

---

## 8.2 tenant_modules

**Purpose:** Tenant-specific module settings.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | NO | - | Foreign key to tenants |
| module_id | BIGINT UNSIGNED | NO | - | Foreign key to modules |
| is_enabled | TINYINT(1) | NO | 1 | 1=Enabled for tenant |
| settings | JSON | YES | NULL | Module-specific settings |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_tenant_modules_unique (tenant_id, module_id)

---

## 8.3 custom_fields

**Purpose:** Custom field definitions.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| model_type | VARCHAR(100) | NO | - | Model type (product, customer, invoice) |
| field_name | VARCHAR(100) | NO | - | Internal field name |
| field_label | VARCHAR(100) | NO | - | Display label |
| field_type | ENUM('text','number','date','select','multi-select','textarea','file') | NO | 'text' | Field type |
| options | JSON | YES | NULL | Options for select fields |
| is_required | TINYINT(1) | NO | 0 | 1=Required field |
| is_unique | TINYINT(1) | NO | 0 | 1=Unique value |
| validation_rules | TEXT | YES | NULL | Validation rules |
| order | INT | NO | 0 | Display order |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_custom_fields_tenant_id (tenant_id)
- INDEX idx_custom_fields_model_type (model_type)
- UNIQUE INDEX idx_custom_fields_unique (tenant_id, model_type, field_name)

---

## 8.4 custom_field_values

**Purpose:** Custom field values.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| custom_field_id | BIGINT UNSIGNED | NO | - | Foreign key to custom_fields |
| model_id | BIGINT UNSIGNED | NO | - | Model ID |
| value | TEXT | YES | NULL | Field value |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (custom_field_id) REFERENCES custom_fields(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_custom_field_values_unique (custom_field_id, model_id)
- INDEX idx_custom_field_values_model_id (model_id)

---

## 8.5 system_settings

**Purpose:** System settings.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | YES | NULL | Foreign key to branches |
| key | VARCHAR(255) | NO | - | Setting key |
| value | JSON | NO | - | Setting value |
| category | VARCHAR(100) | NO | 'general' | Setting category |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX idx_system_settings_unique (tenant_id, branch_id, key)
- INDEX idx_system_settings_category (category)

---

## 8.6 audit_logs

**Purpose:** Audit trail for all actions.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | YES | NULL | Foreign key to branches |
| user_id | BIGINT UNSIGNED | NO | - | Foreign key to users |
| action | VARCHAR(100) | NO | - | Action performed |
| model_type | VARCHAR(100) | NO | - | Model type |
| model_id | BIGINT UNSIGNED | NO | - | Model ID |
| changes | JSON | YES | NULL | Changes made |
| ip_address | VARCHAR(45) | YES | NULL | IP address |
| user_agent | TEXT | YES | NULL | User agent |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_audit_logs_tenant_id (tenant_id)
- INDEX idx_audit_logs_branch_id (branch_id)
- INDEX idx_audit_logs_user_id (user_id)
- INDEX idx_audit_logs_model_type (model_type)
- INDEX idx_audit_logs_model_id (model_id)
- INDEX idx_audit_logs_created_at (created_at)
- COMPOSITE INDEX idx_audit_logs_user_action (user_id, action)

---

## 8.7 notifications

**Purpose:** System notifications.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | CHAR(36) | NO | - | UUID primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | YES | NULL | Foreign key to branches |
| type | VARCHAR(255) | NO | - | Notification type |
| notifiable_type | VARCHAR(255) | NO | - | Notifiable model type |
| notifiable_id | BIGINT UNSIGNED | NO | - | Notifiable model ID |
| data | JSON | NO | - | Notification data |
| read_at | TIMESTAMP | YES | NULL | Read timestamp |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Foreign Keys:**
- FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
- FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_notifications_tenant_id (tenant_id)
- INDEX idx_notifications_branch_id (branch_id)
- INDEX idx_notifications_notifiable (notifiable_type, notifiable_id)
- INDEX idx_notifications_read_at (read_at)

---

## 8.8 backup_logs

**Purpose:** Backup history tracking.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tenant_id | BIGINT UNSIGNED | YES | NULL | Foreign key to tenants |
| branch_id | BIGINT UNSIGNED | YES | NULL | Foreign key to branches |
| type | ENUM('database','files','full') | NO | - | Backup type |
| filename | VARCHAR(255) | NO | - | Backup filename |
| size | BIGINT | YES | NULL | File size in bytes |
| status | ENUM('pending','running','completed','failed') | NO | 'pending' | Backup status |
| error_message | TEXT | YES | NULL | Error message if failed |
| started_at | TIMESTAMP | YES | NULL | Start timestamp |
| completed_at | TIMESTAMP | YES | NULL | Completion timestamp |
| created_at | TIMESTAMP | YES | NULL | Creation timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_backup_logs_tenant_id (tenant_id)
- INDEX idx_backup_logs_branch_id (branch_id)
- INDEX idx_backup_logs_status (status)
- INDEX idx_backup_logs_created_at (created_at)

---

# 9. Relationship Diagram (ERD Overview)

## Table Relationships Summary

```
tenants (1) ────┬── (N) branches
                ├── (N) users
                ├── (N) categories
                ├── (N) brands
                ├── (N) products
                ├── (N) suppliers
                ├── (N) customers
                ├── (N) sales_invoices
                ├── (N) purchase_orders
                ├── (N) payments
                ├── (N) expenses
                └── (N) shipments

branches (1) ───┬── (N) product_batches
                ├── (N) inventory_locations
                ├── (N) sales_invoices
                ├── (N) purchase_orders
                ├── (N) expenses
                ├── (N) shipments
                ├── (N) payments
                ├── (N) branch_users
                └── (N) customer_branch_balances

users (1) ──────┬── (N) sales_invoices (created_by)
                ├── (N) purchase_orders (created_by)
                ├── (N) payments (recorded_by)
                ├── (N) expenses (recorded_by)
                ├── (N) shipments (created_by)
                ├── (N) audit_logs
                ├── (N) branch_users
                └── (N) inventory_transfers (requested_by/approved_by/etc.)

products (1) ───┬── (N) product_variants
                ├── (N) product_images
                ├── (N) product_batches
                ├── (N) sales_invoice_items
                ├── (N) purchase_order_items
                ├── (N) inventory_transfers
                └── (N) shipment_returns

product_batches (1) ───┬── (N) sales_invoice_items
                       ├── (N) inventory_transfers
                       └── (N) shipment_returns

suppliers (1) ───┬── (N) product_batches
                ├── (N) purchase_orders
                └── (N) payments

customers (1) ───┬── (N) sales_invoices
                ├── (N) payments
                ├── (N) shipments
                ├── (N) customer_branch_balances
                └── (N) sales_invoice_items

sales_invoices (1) ───┬── (N) sales_invoice_items
                     ├── (N) payments
                     └── (N) shipments

purchase_orders (1) ───┬── (N) purchase_order_items
                      ├── (N) product_batches
                      └── (N) payments

expenses (N) ───────── (1) expense_categories

shipments (1) ──────── (N) shipment_returns

permissions (N) ────── (N) roles (via role_has_permissions)
users (N) ──────────── (N) roles (via model_has_roles)
users (N) ──────────── (N) permissions (via model_has_permissions)
```

---

# 10. Database Optimization Summary

## 10.1 Indexing Strategy

| Table | Index Type | Columns | Purpose |
|-------|------------|---------|---------|
| products | UNIQUE | sku, tenant_id | Unique product identification |
| products | INDEX | name | Product search optimization |
| products | FULLTEXT | name | Text search |
| product_batches | COMPOSITE | product_id, branch_id | Branch-specific inventory queries |
| sales_invoices | COMPOSITE | customer_id, branch_id | Customer-branch sales queries |
| sales_invoices | COMPOSITE | invoice_date, payment_status | Date-based status queries |
| inventory_transfers | COMPOSITE | from_branch_id, to_branch_id | Transfer tracking |
| audit_logs | COMPOSITE | user_id, action | User activity tracking |
| customers | COMPOSITE | name, phone | Customer search |

---

## 10.2 Partitioning Strategy (For Large Datasets)

| Table | Partition Key | When to Partition |
|-------|--------------|-------------------|
| sales_invoices | invoice_date | > 1 million rows |
| sales_invoice_items | created_at | > 2 million rows |
| audit_logs | created_at | > 5 million rows |
| product_batches | branch_id | > 500k rows |

---

## 10.3 Data Types Optimization

| Table | Column | Data Type | Why |
|-------|--------|-----------|-----|
| All | id | BIGINT UNSIGNED | Large scale support |
| All | is_active | TINYINT(1) | Minimal storage |
| Users | password | VARCHAR(255) | Hash compatibility |
| Products | price | DECIMAL(15,2) | Precise currency |
| Text fields | TEXT | TEXT | Flexible content |
| JSON fields | JSON | JSON | Flexible structure |

---

## 10.4 Foreign Key Optimization

| Constraint | Action | Why |
|------------|--------|-----|
| ON DELETE CASCADE | Delete child records | Data integrity |
| ON DELETE SET NULL | Set parent to null | Preserve data |
| ON UPDATE CASCADE | Update child records | Referential integrity |
| NO ACTION | Prevent deletion if referenced | Data protection |

---

## 10.5 Performance Query Examples

**1. Branch Inventory Summary:**
```sql
SELECT 
    p.name,
    p.sku,
    pb.branch_id,
    SUM(pb.remaining_quantity) as total_stock,
    AVG(pb.cost_per_unit) as avg_cost,
    p.reorder_level
FROM product_batches pb
JOIN products p ON p.id = pb.product_id
WHERE pb.branch_id = ? 
  AND pb.is_active = 1
GROUP BY p.id, pb.branch_id
HAVING total_stock < p.reorder_level;
```

**2. Branch Sales Performance:**
```sql
SELECT 
    b.name as branch_name,
    COUNT(si.id) as invoice_count,
    SUM(si.net_amount) as total_revenue,
    SUM(si.net_amount - (si_item.quantity * si_item.cost_per_unit)) as total_profit,
    AVG(si.net_amount) as avg_invoice_value
FROM sales_invoices si
JOIN branches b ON b.id = si.branch_id
JOIN sales_invoice_items si_item ON si_item.sales_invoice_id = si.id
WHERE si.invoice_date BETWEEN ? AND ?
  AND si.payment_status != 'unpaid'
GROUP BY b.id;
```

**3. Stock Transfer History:**
```sql
SELECT 
    it.transfer_number,
    fb.name as from_branch,
    tb.name as to_branch,
    p.name as product_name,
    it.quantity,
    it.status,
    it.request_date,
    it.received_date
FROM inventory_transfers it
JOIN branches fb ON fb.id = it.from_branch_id
JOIN branches tb ON tb.id = it.to_branch_id
JOIN products p ON p.id = it.product_id
WHERE it.tenant_id = ?
  AND it.request_date BETWEEN ? AND ?
ORDER BY it.request_date DESC;
```

---

**End of Database Schema Document**

---

*This document provides a complete database structure with multi-branch support, optimized for MySQL 8.0+. All tables include proper indexing, foreign key constraints, and data types for optimal performance.*

---

**© 2026 - All Rights Reserved**