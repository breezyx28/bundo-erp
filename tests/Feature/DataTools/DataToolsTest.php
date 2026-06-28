<?php

namespace Tests\Feature\DataTools;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Branch\BranchContext;
use App\Services\DataTransfer\ExportService;
use App\Services\DataTransfer\ImportService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataToolsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

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
    }

    protected function writeCsv(string $name, string $contents): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_imports_valid_rows_and_records_failures(): void
    {
        $csv = $this->writeCsv('products.csv', implode("\n", [
            'name,sku,cost_price,selling_price,reorder_level',
            'Leather Boot,LB-1,40,100,5',
            ',MISSING-NAME,10,20,1', // invalid: missing required name
            'Canvas Shoe,CS-1,20,55,3',
        ]));

        $log = app(ImportService::class)->run('products', $csv);

        $this->assertSame(3, $log->total_rows);
        $this->assertSame(2, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertSame(ImportLog::STATUS_COMPLETED, $log->status);
        $this->assertNotNull($log->errors);

        $this->assertDatabaseHas('products', ['sku' => 'LB-1', 'name' => 'Leather Boot']);
        $this->assertDatabaseHas('products', ['sku' => 'CS-1', 'name' => 'Canvas Shoe']);
        $this->assertDatabaseMissing('products', ['sku' => 'MISSING-NAME']);
    }

    public function test_export_service_builds_rows(): void
    {
        Product::factory()->for($this->tenant)->create(['name' => 'Alpha', 'sku' => 'A-1']);
        Customer::factory()->for($this->tenant)->create(['name' => 'Beta']);

        $products = app(ExportService::class)->build('products');
        $this->assertContains('name', $products['headings']);
        $this->assertCount(1, $products['rows']);
        $this->assertSame('Alpha', $products['rows'][0][0]);

        $customers = app(ExportService::class)->build('customers');
        $this->assertSame('Beta', $customers['rows'][0][0]);
    }

    public function test_backup_service_lists_archives(): void
    {
        Storage::fake('local');
        $dir = app(BackupService::class)->directory();
        Storage::disk('local')->put($dir.'/2026-06-26-020000.zip', 'dummy');

        $backups = app(BackupService::class)->list();

        $this->assertCount(1, $backups);
        $this->assertSame('2026-06-26-020000.zip', $backups[0]['name']);
    }

    public function test_data_tools_and_notifications_pages_render(): void
    {
        $this->get(route('data-tools.index'))->assertOk()->assertSeeLivewire('data-tools.index');
        $this->get(route('notifications.index'))->assertOk()->assertSeeLivewire('notifications.index');
    }

    public function test_export_download_returns_spreadsheet(): void
    {
        Product::factory()->for($this->tenant)->create(['name' => 'Alpha', 'sku' => 'A-1']);

        $response = $this->get(route('data.export', ['type' => 'products', 'format' => 'csv']));
        $response->assertOk();
    }
}
