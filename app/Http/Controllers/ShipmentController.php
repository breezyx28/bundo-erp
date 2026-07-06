<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\LogisticsCompany;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentReturn;
use App\Services\Shipping\ShippingService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['tracking_number', 'status', 'shipping_cost'];

    public function index(Request $request, ShippingService $service, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $statusFilter = (string) $request->string('status');
        $from = (string) ($request->string('from') ?: now()->startOfMonth()->toDateString());
        $to = (string) ($request->string('to') ?: now()->toDateString());

        $report = $service->report($from, $to);

        $query = Shipment::query()
            ->search($search)
            ->status($statusFilter)
            ->with(['customer:id,name', 'logisticsCompany:id,name', 'invoice:id,invoice_number']);
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Shipments/Index', [
            'shipments' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Shipment $s) => [
                    'id' => $s->id,
                    'tracking_number' => $s->tracking_number,
                    'invoice_number' => $s->invoice?->invoice_number,
                    'customer' => $s->customer?->name,
                    'dispatch_city' => $s->dispatch_city,
                    'delivery_city' => $s->delivery_city,
                    'company' => $s->logisticsCompany?->name,
                    'status' => $s->status,
                    'shipping_cost' => Money::format($s->shipping_cost),
                    'next_status' => $s->nextStatus(),
                    'is_final' => $s->isFinal(),
                ]),
            'companyOptions' => LogisticsCompany::query()->active()->orderBy('name')->get(['id', 'name']),
            'invoiceOptions' => $catalog->openSalesInvoicesForShipment(),
            'statusOptions' => collect(array_keys(Shipment::TRANSITIONS))
                ->map(fn ($s) => ['value' => $s, 'label' => __('shipping.status.'.$s)])->all(),
            'modeOptions' => [
                ['value' => 'per_invoice', 'label' => __('shipping.mode.per_invoice')],
                ['value' => 'global', 'label' => __('shipping.mode.global')],
            ],
            'report' => [
                'total' => $report['total'],
                'shipping_cost' => Money::format($report['shipping_cost']),
                'by_status' => $report['by_status'],
                'top_cities' => $report['top_cities'],
                'top_companies' => $report['top_companies'],
            ],
            'deliveredStatus' => Shipment::STATUS_DELIVERED,
            'detail' => Inertia::optional(fn () => $this->detailData($request->integer('detail'))),
            'returnOptions' => Inertia::optional(fn () => $this->returnProductOptions($request->integer('return_shipment'))),
            'sortOptions' => [
                ['value' => 'tracking_number', 'label' => __('shipping.tracking')],
                ['value' => 'status', 'label' => __('common.status')],
                ['value' => 'shipping_cost', 'label' => __('shipping.shipping_cost')],
            ],
            'filters' => [
                'search' => $search,
                'status' => $statusFilter ?: null,
                'from' => $from,
                'to' => $to,
                'sort' => in_array($request->string('sort')->toString(), $this->sortable, true) ? $request->string('sort')->toString() : null,
                'direction' => strtolower((string) $request->string('direction')) === 'asc' ? 'asc' : 'desc',
            ],
            'canManage' => Gate::allows('shipping.manage'),
        ]);
    }

    public function store(Request $request, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        $data = $request->validate([
            'sales_invoice_id' => 'required|integer|exists:sales_invoices,id',
            'logistics_company_id' => 'required|integer|exists:logistics_companies,id',
            'dispatch_city' => 'required|string|max:100',
            'delivery_city' => 'required|string|max:100',
            'number_of_boxes' => 'integer|min:0',
            'shipping_cost' => 'numeric|min:0',
            'cost_mode' => 'required|in:per_invoice,global',
            'tracking_number' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $invoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
            $service->createShipment($invoice, $data);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('shipping.created'));

        return redirect()->route('shipments.index');
    }

    public function advance(Shipment $shipment, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        try {
            $service->advance($shipment);
            $this->toastSuccess(__('shipping.status_updated'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('shipments.index');
    }

    public function deliver(Request $request, Shipment $shipment, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        $request->validate(['pod' => 'nullable|image|max:4096']);

        $podPath = $request->hasFile('pod') ? $request->file('pod')->store('pod', 'public') : null;

        try {
            $service->advance($shipment, Shipment::STATUS_DELIVERED, $podPath);
            $this->toastSuccess(__('shipping.delivered'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('shipments.index');
    }

    public function registerReturn(Request $request, Shipment $shipment, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        $data = $request->validate([
            'return_product_id' => 'required|integer|exists:products,id',
            'return_quantity' => 'required|integer|min:1',
            'return_reason' => 'nullable|string|max:255',
        ]);

        try {
            $service->registerReturn($shipment, [
                'product_id' => $data['return_product_id'],
                'quantity' => $data['return_quantity'],
                'reason' => ($data['return_reason'] ?? null) ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('shipping.return_registered'));

        return redirect()->route('shipments.index');
    }

    public function processReturn(ShipmentReturn $return, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        try {
            $service->processReturn($return);
            $this->toastSuccess(__('shipping.return_processed'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->back();
    }

    public function rejectReturn(ShipmentReturn $return, ShippingService $service): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        $service->rejectReturn($return);

        $this->toastWarning(__('shipping.return_rejected'));

        return redirect()->back();
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    protected function returnProductOptions(?int $shipmentId): array
    {
        if (! $shipmentId) {
            return [];
        }

        $shipment = Shipment::with('invoice.items.product:id,name,sku')->find($shipmentId);

        return ($shipment?->invoice?->items ?? collect())
            ->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => $item->product?->name,
                'sku' => $item->product?->sku,
            ])
            ->unique('id')->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function detailData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $s = Shipment::query()
            ->with(['customer:id,name,phone', 'logisticsCompany:id,name', 'invoice:id,invoice_number', 'returns.product:id,name'])
            ->find($id);

        if (! $s) {
            return null;
        }

        return [
            'id' => $s->id,
            'tracking_number' => $s->tracking_number,
            'invoice_number' => $s->invoice?->invoice_number,
            'status' => $s->status,
            'cost_mode' => $s->cost_mode,
            'customer' => $s->customer?->name,
            'dispatch_city' => $s->dispatch_city,
            'delivery_city' => $s->delivery_city,
            'company' => $s->logisticsCompany?->name,
            'number_of_boxes' => $s->number_of_boxes,
            'shipping_cost' => Money::format($s->shipping_cost),
            'waybill_number' => $s->waybill_number,
            'shipment_value' => Money::format($s->shipment_value),
            'pod_url' => $s->pod_image ? Storage::url($s->pod_image) : null,
            'returns' => $s->returns->map(fn (ShipmentReturn $r) => [
                'id' => $r->id,
                'product' => $r->product?->name,
                'quantity' => $r->quantity,
                'reason' => $r->reason,
                'status' => $r->status,
            ])->all(),
        ];
    }
}
