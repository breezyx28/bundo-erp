<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\Cache;

/**
 * Cached, tenant-scoped option lists for searchable form selects.
 * Loaded on demand (e.g. when a modal opens) to keep list pages fast.
 */
class FormSelectCatalog
{
    public function __construct(protected BranchContext $branch) {}

    /** @return list<array{id:int,name:string,sku:?string}> */
    public function products(bool $withPrice = false): array
    {
        return Cache::remember($this->key('products', $withPrice), now()->addMinutes(15), function () use ($withPrice) {
            $columns = $withPrice ? ['id', 'name', 'sku', 'selling_price'] : ['id', 'name', 'sku'];

            return Product::query()
                ->active()
                ->orderBy('name')
                ->get($columns)
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $withPrice
                        ? $p->name.' · '.number_format((float) $p->selling_price, 2)
                        : $p->name,
                    'sku' => $p->sku,
                ])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function customers(): array
    {
        return Cache::remember($this->key('customers'), now()->addMinutes(15), function () {
            return Customer::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function suppliers(): array
    {
        return Cache::remember($this->key('suppliers'), now()->addMinutes(15), function () {
            return Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Supplier $s) => ['id' => $s->id, 'name' => $s->name])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function categories(): array
    {
        return Cache::remember($this->key('categories'), now()->addMinutes(30), function () {
            return Category::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function variantsFor(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return Cache::remember($this->key("variants:{$productId}"), now()->addMinutes(15), function () use ($productId) {
            return ProductVariant::query()
                ->where('product_id', $productId)
                ->orderBy('size')
                ->orderBy('color')
                ->get()
                ->map(fn (ProductVariant $v) => ['id' => $v->id, 'name' => $v->label()])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function openSalesInvoicesForShipment(): array
    {
        return Cache::remember($this->key('shipment-invoices'), now()->addMinutes(5), function () {
            return SalesInvoice::query()
                ->whereNotNull('customer_id')
                ->whereNotIn('id', Shipment::query()->pluck('sales_invoice_id'))
                ->with('customer:id,name')
                ->latest('id')
                ->limit(200)
                ->get()
                ->map(fn (SalesInvoice $i) => [
                    'id' => $i->id,
                    'name' => $i->invoice_number.' — '.($i->customer?->name ?? ''),
                ])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function brands(): array
    {
        return Cache::remember($this->key('brands'), now()->addMinutes(30), function () {
            return Brand::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Brand $b) => ['id' => $b->id, 'name' => $b->name])
                ->all();
        });
    }

    /** @return list<array{id:int,name:string}> */
    public function purchaseOrders(): array
    {
        return Cache::remember($this->key('purchase-orders'), now()->addMinutes(10), function () {
            return PurchaseOrder::query()
                ->latest('id')
                ->limit(200)
                ->get(['id', 'po_number'])
                ->map(fn (PurchaseOrder $po) => ['id' => $po->id, 'name' => $po->po_number])
                ->all();
        });
    }

    public function flush(?string $scope = null): void
    {
        $tenantId = $this->branch->currentTenantId() ?? 0;

        $suffixes = match ($scope) {
            'customers' => ['customers'],
            'suppliers' => ['suppliers'],
            'categories' => ['categories'],
            'brands' => ['brands'],
            'products' => ['products:0', 'products:1'],
            'purchase-orders' => ['purchase-orders'],
            'shipment-invoices' => ['shipment-invoices'],
            default => [
                'products:0', 'products:1', 'customers', 'suppliers', 'categories',
                'brands', 'shipment-invoices', 'purchase-orders',
            ],
        };

        foreach ($suffixes as $suffix) {
            Cache::forget("form-select:{$tenantId}:{$suffix}");
        }
    }

    protected function key(string $suffix, mixed $flag = null): string
    {
        $tenantId = $this->branch->currentTenantId() ?? 0;
        $flagSuffix = $flag === null ? '' : ':'.(int) (bool) $flag;

        return "form-select:{$tenantId}:{$suffix}{$flagSuffix}";
    }
}
