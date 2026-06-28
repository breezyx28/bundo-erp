<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12 hardening.
 *
 * Document numbers (invoice/PO/transfer) are generated per-branch by
 * DocumentNumberService, so two branches in the same tenant both start at
 * INV-00001. The original per-tenant unique constraints therefore collide in a
 * multi-branch tenant. We rescope uniqueness to (tenant_id, branch_id, number)
 * to match the numbering strategy (transfers key on from_branch_id, their
 * numbering branch), and add a composite reporting index for the
 * hot payments aggregation path (branch + direction + date).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'invoice_number']);
            $table->unique(['tenant_id', 'branch_id', 'invoice_number'], 'sales_invoices_branch_number_unique');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'po_number']);
            $table->unique(['tenant_id', 'branch_id', 'po_number'], 'purchase_orders_branch_number_unique');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'number']);
            $table->unique(['tenant_id', 'from_branch_id', 'number'], 'stock_transfers_branch_number_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['branch_id', 'direction', 'payment_date'], 'payments_branch_direction_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_branch_direction_date_index');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropUnique('stock_transfers_branch_number_unique');
            $table->unique(['tenant_id', 'number']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_branch_number_unique');
            $table->unique(['tenant_id', 'po_number']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_branch_number_unique');
            $table->unique(['tenant_id', 'invoice_number']);
        });
    }
};
