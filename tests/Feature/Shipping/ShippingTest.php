<?php

namespace Tests\Feature\Shipping;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\LogisticsCompany;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentReturn;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use App\Services\Shipping\ShippingService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected ShippingService $shipping;

    protected SalesService $sales;

    protected InventoryService $inventory;

    protected Customer $customer;

    protected LogisticsCompany $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($this->branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        $this->shipping = app(ShippingService::class);
        $this->sales = app(SalesService::class);
        $this->inventory = app(InventoryService::class);

        $this->customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);
        $this->company = LogisticsCompany::create(['tenant_id' => $this->tenant->id, 'name' => 'Sudan Express', 'is_active' => true]);
    }

    protected function shippableInvoice(int $qty = 5, float $cost = 500, float $price = 1000): array
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => $price]);
        $this->inventory->receive($this->branch->id, $product->id, $qty, $cost);

        $invoice = $this->sales->createInvoice([
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CREDIT,
            'payment_method' => 'cash',
            'due_date' => now()->addDays(30)->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $price],
        ]);

        return [$invoice, $product];
    }

    protected function newShipment(SalesInvoice $invoice): Shipment
    {
        return $this->shipping->createShipment($invoice, [
            'logistics_company_id' => $this->company->id,
            'dispatch_city' => 'Atbara',
            'delivery_city' => 'Madani',
            'number_of_boxes' => 3,
            'shipping_cost' => 5000,
            'cost_mode' => Shipment::MODE_PER_INVOICE,
        ]);
    }

    public function test_status_machine_advances_forward_and_sets_timestamps(): void
    {
        [$invoice] = $this->shippableInvoice();
        $shipment = $this->newShipment($invoice);

        $this->assertSame(Shipment::STATUS_PENDING, $shipment->status);

        $this->shipping->advance($shipment); // processing
        $this->shipping->advance($shipment); // handed_to_logistics
        $shipment->refresh();
        $this->assertSame(Shipment::STATUS_HANDED, $shipment->status);
        $this->assertNotNull($shipment->dispatched_at);

        $this->shipping->advance($shipment); // in_transit
        $this->shipping->advance($shipment); // arrived
        $this->shipping->advance($shipment, Shipment::STATUS_DELIVERED, 'pod/x.jpg');
        $shipment->refresh();
        $this->assertSame(Shipment::STATUS_DELIVERED, $shipment->status);
        $this->assertNotNull($shipment->delivered_at);
        $this->assertSame('pod/x.jpg', $shipment->pod_image);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        [$invoice] = $this->shippableInvoice();
        $shipment = $this->newShipment($invoice);

        $this->expectException(LogicException::class);
        $this->shipping->advance($shipment, Shipment::STATUS_DELIVERED);
    }

    public function test_processing_a_return_restores_stock_and_marks_shipment_returned(): void
    {
        [$invoice, $product] = $this->shippableInvoice(qty: 5, cost: 500);
        $shipment = $this->newShipment($invoice);

        $this->assertSame(0, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));

        $return = $this->shipping->registerReturn($shipment, [
            'product_id' => $product->id,
            'quantity' => 2,
            'reason' => 'Customer refused',
        ]);

        $this->assertSame(ShipmentReturn::STATUS_PENDING, $return->status);

        $this->shipping->processReturn($return);
        $return->refresh();
        $shipment->refresh();

        $this->assertSame(ShipmentReturn::STATUS_PROCESSED, $return->status);
        $this->assertSame(Shipment::STATUS_RETURNED, $shipment->status);
        $this->assertSame(2, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
    }

    public function test_processed_return_cannot_be_reprocessed(): void
    {
        [$invoice, $product] = $this->shippableInvoice();
        $shipment = $this->newShipment($invoice);
        $return = $this->shipping->registerReturn($shipment, ['product_id' => $product->id, 'quantity' => 1]);

        $this->shipping->processReturn($return);

        $this->expectException(LogicException::class);
        $this->shipping->processReturn($return->refresh());
    }

    public function test_shipping_pages_render(): void
    {
        [$invoiceA] = $this->shippableInvoice();
        $this->newShipment($invoiceA);

        $this->get(route('shipments.index'))->assertOk()->assertSee('Madani');
        $this->get(route('logistics.index'))->assertOk()->assertSee('Sudan Express');
    }

    public function test_report_aggregates_status_cost_and_top_lists(): void
    {
        [$invoiceA] = $this->shippableInvoice();
        [$invoiceB] = $this->shippableInvoice();
        $this->newShipment($invoiceA);
        $this->newShipment($invoiceB);

        $report = $this->shipping->report(now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->assertSame(2, $report['total']);
        $this->assertSame(10000.0, $report['shipping_cost']);
        $this->assertSame(2, $report['by_status']['pending']);
        $this->assertSame('Madani', $report['top_cities'][0]['city']);
        $this->assertSame('Sudan Express', $report['top_companies'][0]['company']);
    }
}
