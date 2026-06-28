<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('sale_type')->default('cash'); // cash|credit
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_usd', 15, 2)->default(0);
            $table->string('discount_type')->nullable(); // percentage|fixed
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('net_amount_usd', 15, 2)->default(0);
            $table->decimal('cost_total', 15, 2)->default(0); // captured COGS
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // paid|partial|unpaid
            $table->string('payment_method')->nullable();
            $table->string('transaction_number')->nullable();
            $table->decimal('exchange_rate', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index('customer_id');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('payment_status');
            $table->index(['customer_id', 'branch_id']);
            $table->index(['invoice_date', 'payment_status']);
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('unit_price_usd', 15, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0); // COGS from the batch
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('total_usd', 15, 2)->default(0);
            $table->timestamps();

            $table->index('sales_invoice_id');
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
