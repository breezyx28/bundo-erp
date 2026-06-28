<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('default_branch_id')->nullable()->after('tenant_id')->constrained('branches')->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('profile_photo')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('profile_photo')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->json('settings')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('default_branch_id');
            $table->dropColumn(['phone', 'profile_photo', 'is_active', 'last_login_at', 'settings', 'deleted_at']);
        });
    }
};
