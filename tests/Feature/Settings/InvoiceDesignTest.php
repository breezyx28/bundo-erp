<?php

namespace Tests\Feature\Settings;

use App\Models\Branch;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use App\Services\Settings\SettingsManager;
use App\Support\InvoiceDesign;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDesignTest extends TestCase
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

    public function test_settings_page_lists_invoice_designs(): void
    {
        $this->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.invoice_design', 'classic')
                ->has('invoiceDesigns', 3));
    }

    public function test_can_save_invoice_design(): void
    {
        $this->put(route('settings.invoice'), [
            'invoice_prefix' => 'INV-',
            'invoice_footer' => 'Thanks',
            'invoice_design' => 'minimal',
        ])->assertRedirect(route('settings.index'));

        $this->assertSame('minimal', InvoiceDesign::currentKey());
    }

    public function test_rejects_unknown_invoice_design(): void
    {
        $this->put(route('settings.invoice'), [
            'invoice_prefix' => 'INV-',
            'invoice_footer' => '',
            'invoice_design' => 'unknown',
        ])->assertSessionHasErrors('invoice_design');
    }

    public function test_preview_routes_render_each_design(): void
    {
        foreach (InvoiceDesign::keys() as $design) {
            $this->get(route('settings.invoice.preview', $design))
                ->assertOk()
                ->assertSee('INV-0001');
        }
    }

    public function test_print_uses_selected_design(): void
    {
        app(SettingsManager::class)->set('design', 'minimal', group: 'invoice');

        $product = Product::factory()->for($this->tenant)->create(['cost_price' => 500, 'selling_price' => 1000]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 10, 500);

        $invoice = app(SalesService::class)->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->get(route('invoices.print', $invoice->id))
            ->assertOk()
            ->assertSee('class="top"', false);
    }
}
