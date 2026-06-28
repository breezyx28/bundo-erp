<?php

use App\Http\Controllers\DataExportController;
use App\Http\Controllers\InvoiceDocumentController;
use App\Http\Controllers\ReportExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'auth.login')->name('login')->middleware('throttle:10,1');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        Session::put('locale', $locale);

        if ($user = Auth::user()) {
            $settings = $user->settings ?? [];
            $settings['locale'] = $locale;
            $user->forceFill(['settings' => $settings])->saveQuietly();
        }
    }

    return back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::livewire('/', 'dashboard.index')->name('dashboard');

    // Onboarding (Phase 13)
    Route::livewire('/onboarding', 'onboarding.index')->name('onboarding.index');

    // Platform / Super Admin (Phase 13)
    Route::middleware('super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::livewire('/', 'platform.index')->name('dashboard');
        Route::livewire('/tenants', 'platform.tenants.index')->name('tenants');
        Route::post('/exit-tenant', function () {
            app(\App\Services\Tenancy\TenantContext::class)->setTenant(null);

            return redirect()->route('platform.dashboard');
        })->name('exit-tenant');
    });

    // Tenant administration (Phase 13)
    Route::livewire('/branches', 'branches.index')->name('branches.index')->middleware('permission:branches.manage');
    Route::livewire('/settings', 'settings.index')->name('settings.index')->middleware('permission:settings.manage');
    Route::livewire('/users', 'users.index')->name('users.index')->middleware('permission:users.manage');

    // Master data (Phase 2)
    Route::livewire('/products', 'products.index')->name('products.index')->middleware('permission:products.view');
    Route::livewire('/categories', 'categories.index')->name('categories.index')->middleware('permission:categories.manage');
    Route::livewire('/brands', 'brands.index')->name('brands.index')->middleware('permission:brands.manage');
    Route::livewire('/suppliers', 'suppliers.index')->name('suppliers.index')->middleware('permission:suppliers.view');
    Route::livewire('/customers', 'customers.index')->name('customers.index')->middleware('permission:customers.view');

    // Inventory (Phase 3)
    Route::livewire('/inventory', 'inventory.index')->name('inventory.index')->middleware('permission:inventory.view');
    Route::livewire('/transfers', 'transfers.index')->name('transfers.index')->middleware('permission:inventory.view');

    // Purchasing (Phase 4)
    Route::livewire('/purchases', 'purchases.index')->name('purchases.index')->middleware('permission:purchases.view');

    // Sales (Phase 5)
    Route::livewire('/sales', 'sales.index')->name('sales.index')->middleware('permission:invoices.view');

    // Debts & Collections (Phase 6)
    Route::livewire('/debts', 'debts.index')->name('debts.index')->middleware('permission:debts.view');

    // Expenses (Phase 7)
    Route::livewire('/expenses', 'expenses.index')->name('expenses.index')->middleware('permission:expenses.view');
    Route::livewire('/expense-categories', 'expense-categories.index')->name('expense-categories.index')->middleware('permission:expenses.view');

    // Shipping & Logistics (Phase 8)
    Route::livewire('/shipments', 'shipments.index')->name('shipments.index')->middleware('permission:shipping.view');
    Route::livewire('/logistics', 'logistics.index')->name('logistics.index')->middleware('permission:shipping.view');

    // Dashboards & Financial Reports (Phase 9)
    Route::livewire('/reports', 'reports.index')->name('reports.index')->middleware('permission:reports.view');
    Route::get('/reports/export', ReportExportController::class)->name('reports.export')->middleware('permission:reports.view');

    // Data Analysis & Prediction (Phase 10)
    Route::livewire('/analytics', 'analytics.index')->name('analytics.index')->middleware('permission:analytics.view');

    // Import / Export & Backups (Phase 11)
    Route::livewire('/data-tools', 'data-tools.index')->name('data-tools.index')->middleware('permission:settings.manage');
    Route::get('/data-tools/export', [DataExportController::class, 'export'])->name('data.export')->middleware('permission:settings.manage');
    Route::get('/data-tools/template', [DataExportController::class, 'template'])->name('data.template')->middleware('permission:settings.manage');

    // Notifications (Phase 11)
    Route::livewire('/notifications', 'notifications.index')->name('notifications.index');
    Route::get('/invoices/{invoice}/print', [InvoiceDocumentController::class, 'print'])->name('invoices.print')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceDocumentController::class, 'pdf'])->name('invoices.pdf')->middleware('permission:invoices.view');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
