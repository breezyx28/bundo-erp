<?php

namespace App\Services\Shipping;

use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentReturn;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Shipment lifecycle and logistics.
 *
 * The status machine is forward-only (see Shipment::TRANSITIONS). Processing a
 * "Return from Shipment" document restores the returned units to branch stock at
 * their original batch cost (Option B from the PRD) and marks the shipment returned.
 */
class ShippingService
{
    public function __construct(
        protected InventoryService $inventory,
        protected BranchContext $context,
        protected NotificationService $notifications,
    ) {}

    /**
     * @param  array{logistics_company_id:int, dispatch_city:string, delivery_city:string, number_of_boxes?:int, shipping_cost?:float, cost_mode?:string, tracking_number?:?string, waybill_number?:?string, notes?:?string}  $data
     */
    public function createShipment(SalesInvoice $invoice, array $data): Shipment
    {
        if ($invoice->customer_id === null) {
            throw new LogicException('Only customer invoices can be shipped.');
        }

        return Shipment::create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'logistics_company_id' => $data['logistics_company_id'],
            'tracking_number' => $data['tracking_number'] ?? null,
            'waybill_number' => $data['waybill_number'] ?? null,
            'dispatch_city' => $data['dispatch_city'],
            'delivery_city' => $data['delivery_city'],
            'number_of_boxes' => $data['number_of_boxes'] ?? 0,
            'shipment_value' => (float) $invoice->net_amount,
            'shipping_cost' => (float) ($data['shipping_cost'] ?? 0),
            'cost_mode' => $data['cost_mode'] ?? Shipment::MODE_PER_INVOICE,
            'status' => Shipment::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Advance a shipment to the next status (or an explicit allowed status).
     */
    public function advance(Shipment $shipment, ?string $to = null, ?string $podImage = null): void
    {
        $to ??= $shipment->nextStatus();

        if ($to === null || ! $shipment->canTransitionTo($to)) {
            throw new LogicException("Cannot move shipment from '{$shipment->status}' to '".($to ?? 'null')."'.");
        }

        $attributes = ['status' => $to];

        if ($to === Shipment::STATUS_HANDED) {
            $attributes['dispatched_at'] = now();
        }

        if ($to === Shipment::STATUS_DELIVERED) {
            $attributes['delivered_at'] = now();

            if ($podImage) {
                $attributes['pod_image'] = $podImage;
            }
        }

        $shipment->update($attributes);

        rescue(fn () => $this->notifications->shipmentUpdated($shipment), report: false);
    }

    /**
     * Register a "Return from Shipment" document (pending approval).
     *
     * @param  array{product_id:int, variant_id?:?int, batch_id?:?int, quantity:int, reason?:?string}  $data
     */
    public function registerReturn(Shipment $shipment, array $data): ShipmentReturn
    {
        if ((int) $data['quantity'] <= 0) {
            throw new LogicException('Return quantity must be positive.');
        }

        return $shipment->returns()->create([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'status' => ShipmentReturn::STATUS_PENDING,
        ]);
    }

    /**
     * Approve and process a return: restore the units to branch stock at batch cost
     * and flag the shipment as returned.
     */
    public function processReturn(ShipmentReturn $return): void
    {
        if ($return->status === ShipmentReturn::STATUS_PROCESSED) {
            throw new LogicException('This return has already been processed.');
        }

        if ($return->status === ShipmentReturn::STATUS_REJECTED) {
            throw new LogicException('A rejected return cannot be processed.');
        }

        DB::transaction(function () use ($return) {
            $shipment = $return->shipment;
            $unitCost = $this->resolveUnitCost($shipment->branch_id, $return);

            $this->inventory->receive(
                branchId: $shipment->branch_id,
                productId: $return->product_id,
                quantity: $return->quantity,
                unitCost: $unitCost,
                variantId: $return->variant_id,
                source: $return,
                type: StockMovement::TYPE_RETURN,
            );

            $return->update([
                'status' => ShipmentReturn::STATUS_PROCESSED,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            $shipment->update(['status' => Shipment::STATUS_RETURNED]);
        });
    }

    public function rejectReturn(ShipmentReturn $return): void
    {
        if ($return->status === ShipmentReturn::STATUS_PROCESSED) {
            throw new LogicException('A processed return cannot be rejected.');
        }

        $return->update([
            'status' => ShipmentReturn::STATUS_REJECTED,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Shipping operations report for a date range.
     *
     * @return array{total:int, by_status:array<string,int>, shipping_cost:float, top_cities:list<array{city:string, count:int}>, top_companies:list<array{company:string, count:int}>}
     */
    public function report(string $from, string $to): array
    {
        $shipments = Shipment::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->with('logisticsCompany:id,name')
            ->get(['id', 'status', 'shipping_cost', 'delivery_city', 'logistics_company_id']);

        $byStatus = [];
        $cities = [];
        $companies = [];
        $cost = 0.0;

        foreach ($shipments as $shipment) {
            $byStatus[$shipment->status] = ($byStatus[$shipment->status] ?? 0) + 1;
            $cost += (float) $shipment->shipping_cost;

            $city = $shipment->delivery_city;
            $cities[$city] = ($cities[$city] ?? 0) + 1;

            $company = $shipment->logisticsCompany->name;
            $companies[$company] = ($companies[$company] ?? 0) + 1;
        }

        return [
            'total' => $shipments->count(),
            'by_status' => $byStatus,
            'shipping_cost' => round($cost, 2),
            'top_cities' => $this->topN($cities, 'city'),
            'top_companies' => $this->topN($companies, 'company'),
        ];
    }

    protected function resolveUnitCost(int $branchId, ShipmentReturn $return): float
    {
        if ($return->batch_id) {
            $cost = ProductBatch::withoutGlobalScopes()->whereKey($return->batch_id)->value('unit_cost');

            if ($cost !== null) {
                return (float) $cost;
            }
        }

        return $this->inventory->weightedAverageCost($branchId, $return->product_id, $return->variant_id);
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{city?:string, company?:string, count:int}>
     */
    protected function topN(array $counts, string $key, int $limit = 5): array
    {
        arsort($counts);

        $result = [];

        foreach (array_slice($counts, 0, $limit, true) as $label => $count) {
            $result[] = [$key => $label, 'count' => $count];
        }

        return $result;
    }
}
