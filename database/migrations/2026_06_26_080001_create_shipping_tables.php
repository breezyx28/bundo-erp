<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->unsignedTinyInteger('rating')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('name');
            $table->index('is_active');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('logistics_company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('waybill_number')->nullable();
            $table->string('dispatch_city');
            $table->string('delivery_city');
            $table->integer('number_of_boxes')->default(0);
            $table->decimal('shipment_value', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('shipping_cost_usd', 15, 2)->nullable();
            $table->string('cost_mode')->default('per_invoice'); // per_invoice|global
            $table->string('status')->default('pending'); // pending|processing|handed_to_logistics|in_transit|arrived|delivered|returned
            $table->string('pod_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('sales_invoice_id');
            $table->index('customer_id');
            $table->index('logistics_company_id');
            $table->index('tracking_number');
            $table->index('status');
            $table->index('delivery_city');
        });

        Schema::create('shipment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected|processed
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_returns');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('logistics_companies');
    }
};
