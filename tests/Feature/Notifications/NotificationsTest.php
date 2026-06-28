<?php

namespace Tests\Feature\Notifications;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $this->user->assignRole('admin');
        $this->user->branches()->attach($this->branch->id);
        $this->actingAs($this->user);
        app(BranchContext::class)->flushCache();
    }

    public function test_payment_received_notifies_branch_users(): void
    {
        $customer = Customer::factory()->for($this->tenant)->create();
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 100]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 10, 40);

        $sales = app(SalesService::class);
        $invoice = $sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'customer_id' => $customer->id,
            'sale_type' => SalesInvoice::TYPE_CREDIT,
        ], [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100],
        ]);

        $sales->recordPayment($invoice, [
            'amount' => 200,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertSame(1, $this->user->fresh()->notifications()->count());
        $this->assertSame('success', $this->user->notifications()->first()->data['level']);
    }

    public function test_scan_low_stock_alerts_branch(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['reorder_level' => 10]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 2, 40); // below reorder

        $alerted = app(NotificationService::class)->scanLowStock($this->tenant->id);

        $this->assertSame(1, $alerted);
        $this->assertSame(1, $this->user->fresh()->unreadNotifications()->count());
        $this->assertSame('alert', $this->user->notifications()->first()->data['level']);
    }

    public function test_scan_overdue_debts_alerts_branch(): void
    {
        $customer = Customer::factory()->for($this->tenant)->create();
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 100]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 10, 40);

        $sales = app(SalesService::class);
        $invoice = $sales->createInvoice([
            'invoice_date' => now()->subDays(60)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'customer_id' => $customer->id,
            'sale_type' => SalesInvoice::TYPE_CREDIT,
        ], [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100],
        ]);
        $this->assertTrue($invoice->fresh()->isOverdue());

        $alerted = app(NotificationService::class)->scanOverdueDebts($this->tenant->id);

        $this->assertSame(1, $alerted);
    }

    public function test_notification_bell_marks_all_read(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['reorder_level' => 10]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 1, 40); // below reorder

        app(NotificationService::class)->scanLowStock($this->tenant->id);
        $this->assertSame(1, $this->user->fresh()->unreadNotifications()->count());

        Livewire::test('layout.notification-bell')
            ->call('markAllRead');

        $this->assertSame(0, $this->user->fresh()->unreadNotifications()->count());
    }
}
