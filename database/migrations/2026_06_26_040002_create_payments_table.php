<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            // sales_invoices arrives in Phase 5; FK added in that migration.
            $table->unsignedBigInteger('sales_invoice_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('direction')->default('out'); // in = received from customer, out = paid to supplier
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('payment_method'); // cash|bank_transfer|check|mobile_money
            $table->string('transaction_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('customer_id');
            $table->index('supplier_id');
            $table->index('sales_invoice_id');
            $table->index('purchase_order_id');
            $table->index('payment_date');
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
