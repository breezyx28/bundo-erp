<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('model_type');           // e.g. App\Models\Product
            $table->string('key');                  // machine name
            $table->string('label');
            $table->string('field_type')->default('text'); // text|number|date|select|boolean|textarea
            $table->json('options')->nullable();    // for select
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'model_type', 'key'], 'custom_fields_unique');
            $table->index(['tenant_id', 'model_type']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->morphs('model'); // model_type, model_id
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'model_type', 'model_id'], 'custom_field_values_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
