<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('batch_number');
            $table->integer('quantity')->default(0);          // remaining units in this batch
            $table->integer('initial_quantity')->default(0);  // units originally received
            $table->decimal('unit_cost', 15, 2)->default(0);  // landed cost per unit (base currency)
            $table->timestamp('received_at')->index();        // FIFO ordering key
            $table->date('expiry_date')->nullable();
            $table->nullableMorphs('source');                 // e.g. purchase order receipt
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'product_id', 'variant_id']);
            $table->index(['branch_id', 'product_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
