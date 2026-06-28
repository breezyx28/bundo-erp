<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('group')->default('general');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string|integer|boolean|json|float
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'group', 'key'], 'settings_scope_unique');
            $table->index(['tenant_id', 'branch_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
