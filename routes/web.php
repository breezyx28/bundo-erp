<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\DataToolsController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceDocumentController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\NotificationActionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store')->middleware('throttle:10,1');
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
    // Shell actions (Inertia topbar): branch scope + notification bell
    Route::post('/branch-context', [BranchContextController::class, 'update'])->name('branch-context.update');
    Route::post('/notifications/{id}/read', [NotificationActionController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationActionController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Onboarding (Phase 13)
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding', [OnboardingController::class, 'finish'])->name('onboarding.finish');

    // Platform / Super Admin (Phase 13)
    Route::middleware('super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformController::class, 'index'])->name('dashboard');
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::post('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/tenants/{tenant}/enter', [TenantController::class, 'enter'])->name('tenants.enter');
        Route::post('/tenants/{tenant}/toggle', [TenantController::class, 'toggleActive'])->name('tenants.toggle');
        Route::post('/exit-tenant', function () {
            app(\App\Services\Tenancy\TenantContext::class)->setTenant(null);

            return redirect()->route('platform.dashboard');
        })->name('exit-tenant');
    });

    // Tenant administration (Phase 13)
    Route::middleware('permission:branches.manage')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    });
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/general', [SettingsController::class, 'saveGeneral'])->name('settings.general');
        Route::put('/settings/currency', [SettingsController::class, 'saveCurrency'])->name('settings.currency');
        Route::post('/settings/branding', [SettingsController::class, 'saveBranding'])->name('settings.branding');
        Route::put('/settings/invoice', [SettingsController::class, 'saveInvoice'])->name('settings.invoice');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    // Master data (Phase 2)
    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
    Route::middleware('permission:categories.manage')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
    Route::middleware('permission:brands.manage')->group(function () {
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    });
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // Inventory (Phase 3)
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/action', [TransferController::class, 'action'])->name('transfers.action');
    });

    // Purchasing (Phase 4)
    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::post('/purchases/{purchase}/place', [PurchaseController::class, 'place'])->name('purchases.place');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('/purchases/{purchase}/payment', [PurchaseController::class, 'payment'])->name('purchases.payment');
    });

    // Sales (Phase 5)
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
        Route::post('/sales/{sale}/payment', [SalesController::class, 'payment'])->name('sales.payment');
        Route::post('/sales/{sale}/void', [SalesController::class, 'void'])->name('sales.void');
    });

    // Debts & Collections (Phase 6)
    Route::middleware('permission:debts.view')->group(function () {
        Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
        Route::post('/debts/collect/{customer}', [DebtController::class, 'collect'])->name('debts.collect');
        Route::post('/debts/remind/{invoice}', [DebtController::class, 'remind'])->name('debts.remind');
    });

    // Expenses (Phase 7)
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
        Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
        Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
    });

    // Shipping & Logistics (Phase 8)
    Route::middleware('permission:shipping.view')->group(function () {
        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::post('/shipments/{shipment}/advance', [ShipmentController::class, 'advance'])->name('shipments.advance');
        Route::post('/shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])->name('shipments.deliver');
        Route::post('/shipments/{shipment}/return', [ShipmentController::class, 'registerReturn'])->name('shipments.return');
        Route::post('/shipment-returns/{return}/process', [ShipmentController::class, 'processReturn'])->name('shipments.return.process');
        Route::post('/shipment-returns/{return}/reject', [ShipmentController::class, 'rejectReturn'])->name('shipments.return.reject');

        Route::get('/logistics', [LogisticsController::class, 'index'])->name('logistics.index');
        Route::post('/logistics', [LogisticsController::class, 'store'])->name('logistics.store');
        Route::put('/logistics/{logistic}', [LogisticsController::class, 'update'])->name('logistics.update');
        Route::delete('/logistics/{logistic}', [LogisticsController::class, 'destroy'])->name('logistics.destroy');
    });

    // Dashboards & Financial Reports (Phase 9)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports.view');
    Route::get('/reports/export', ReportExportController::class)->name('reports.export')->middleware('permission:reports.view');

    // Data Analysis & Prediction (Phase 10)
    Route::middleware('permission:analytics.view')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('/analytics/refresh', [AnalyticsController::class, 'refresh'])->name('analytics.refresh');
    });

    // Import / Export & Backups (Phase 11)
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/data-tools', [DataToolsController::class, 'index'])->name('data-tools.index');
        Route::post('/data-tools/import', [DataToolsController::class, 'import'])->name('data-tools.import');
        Route::post('/data-tools/backup', [DataToolsController::class, 'createBackup'])->name('data-tools.backup');
        Route::get('/data-tools/backup/download/{name}', [DataToolsController::class, 'downloadBackup'])->name('data-tools.backup.download');
        Route::delete('/data-tools/backup', [DataToolsController::class, 'deleteBackup'])->name('data-tools.backup.delete');
        Route::get('/data-tools/export', [DataExportController::class, 'export'])->name('data.export');
        Route::get('/data-tools/template', [DataExportController::class, 'template'])->name('data.template');
    });

    // Notifications (Phase 11)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all');
    Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/preferences', [NotificationController::class, 'savePreferences'])->name('notifications.preferences');
    Route::get('/invoices/{invoice}/print', [InvoiceDocumentController::class, 'print'])->name('invoices.print')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceDocumentController::class, 'pdf'])->name('invoices.pdf')->middleware('permission:invoices.view');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
