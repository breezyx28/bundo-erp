<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('status')->default('posted')->after('sale_type');
            $table->string('hold_label')->nullable()->after('status');
            $table->timestamp('posted_at')->nullable()->after('hold_label');
        });

        DB::table('sales_invoices')->whereNull('status')->update(['status' => 'posted']);

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->change();
            $table->index('status');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $used = [];
        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use (&$used) {
            $base = Str::slug($tenant->name) ?: 'shop-'.$tenant->id;
            $slug = $base;
            $i = 1;
            while (in_array($slug, $used, true) || Tenant::query()->where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $used[] = $slug;
            $tenant->update(['slug' => $slug]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_in_shop')->default(false)->after('is_active');
            $table->boolean('featured_in_shop')->default(false)->after('show_in_shop');
            $table->text('shop_description')->nullable()->after('featured_in_shop');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['show_in_shop', 'featured_in_shop', 'shop_description']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'hold_label', 'posted_at']);
            $table->string('invoice_number')->nullable(false)->change();
        });
    }
};
