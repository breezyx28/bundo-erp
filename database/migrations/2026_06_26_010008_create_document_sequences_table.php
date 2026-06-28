<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');                 // invoice|purchase_order|payment|expense|shipment|transfer
            $table->string('prefix')->default('');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'type'], 'document_sequences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
