<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\PurchaseRequest;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchaseService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $statusFilter = (string) $request->string('status');

        return Inertia::render('Purchases/Index', [
            'orders' => PurchaseOrder::query()
                ->search($search)
                ->status($statusFilter)
                ->with(['supplier:id,name'])
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (PurchaseOrder $o) => [
                    'id' => $o->id,
                    'po_number' => $o->po_number,
                    'supplier' => $o->supplier?->name,
                    'order_date' => $o->order_date?->format('Y-m-d'),
                    'total_amount' => Money::format($o->total_amount),
                    'order_status' => $o->order_status,
                    'payment_status' => $o->payment_status,
                    'outstanding' => (float) $o->outstanding(),
                    'is_editable' => $o->isEditable(),
                    'is_receivable' => $o->isReceivable(),
                ]),
            'supplierOptions' => $catalog->suppliers(),
            'productOptions' => $catalog->products(),
            'statusOptions' => collect(['draft', 'ordered', 'partial', 'received', 'cancelled'])
                ->map(fn ($s) => ['value' => $s, 'label' => __('purchasing.status.'.$s)])->all(),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['value' => $m, 'label' => __('purchasing.methods.'.$m)])->all(),
            'editing' => Inertia::optional(fn () => $this->editData($request->integer('editing'))),
            'receiveDetail' => Inertia::optional(fn () => $this->receiveData($request->integer('receive'))),
            'detail' => Inertia::optional(fn () => $this->detailData($request->integer('detail'))),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter ?: null,
            ],
            'canCreate' => Gate::allows('purchases.create'),
            'canReceive' => Gate::allows('purchases.receive'),
            'canPay' => Gate::allows('payments.create'),
        ]);
    }

    public function store(PurchaseRequest $request, PurchaseService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $service->save([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $data['items'], null);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('common.created'));

        return redirect()->route('purchases.index');
    }

    public function update(PurchaseRequest $request, PurchaseOrder $purchase, PurchaseService $service): RedirectResponse
    {
        if (! $purchase->isEditable()) {
            $this->toastError(__('purchasing.not_editable'));

            return redirect()->route('purchases.index');
        }

        $data = $request->validated();

        try {
            $service->save([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $data['items'], $purchase);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('purchases.index');
    }

    public function place(PurchaseOrder $purchase, PurchaseService $service): RedirectResponse
    {
        Gate::authorize('purchases.update');

        return $this->runGuarded(fn () => $service->place($purchase), __('purchasing.placed'));
    }

    public function cancel(PurchaseOrder $purchase, PurchaseService $service): RedirectResponse
    {
        Gate::authorize('purchases.update');

        return $this->runGuarded(fn () => $service->cancel($purchase->load('items')), __('purchasing.cancelled'));
    }

    public function receive(Request $request, PurchaseOrder $purchase, PurchaseService $service): RedirectResponse
    {
        Gate::authorize('purchases.receive');

        $data = $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'integer|min:0',
        ]);

        try {
            $service->receive($purchase->load('items'), array_map('intval', $data['quantities']));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('purchasing.received'));

        return redirect()->route('purchases.index');
    }

    public function payment(Request $request, PurchaseOrder $purchase, PurchaseService $service): RedirectResponse
    {
        Gate::authorize('payments.create');

        $data = $request->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'pay_date' => 'required|date',
            'pay_reference' => 'nullable|string|max:100',
        ]);

        try {
            $service->recordPayment($purchase, [
                'amount' => $data['pay_amount'],
                'payment_method' => $data['pay_method'],
                'payment_date' => $data['pay_date'],
                'reference_number' => ($data['pay_reference'] ?? null) ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('purchasing.payment_recorded'));

        return redirect()->route('purchases.index');
    }

    protected function runGuarded(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
            $this->toastSuccess($message);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('purchases.index');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function editData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $order = PurchaseOrder::with('items')->find($id);

        if (! $order || ! $order->isEditable()) {
            return null;
        }

        return [
            'id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'order_date' => $order->order_date?->toDateString(),
            'expected_delivery_date' => $order->expected_delivery_date?->toDateString(),
            'notes' => (string) $order->notes,
            'items' => $order->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'cost_per_unit' => (float) $i->cost_per_unit,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function receiveData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $order = PurchaseOrder::with('items.product:id,name')->find($id);

        if (! $order) {
            return null;
        }

        return [
            'id' => $order->id,
            'po_number' => $order->po_number,
            'items' => $order->items->map(fn ($i) => [
                'id' => $i->id,
                'product' => $i->product?->name,
                'outstanding' => $i->outstandingQuantity(),
                'quantity' => $i->quantity,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function detailData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $o = PurchaseOrder::with(['supplier:id,name', 'items.product:id,name', 'items.variant', 'payments'])->find($id);

        if (! $o) {
            return null;
        }

        return [
            'id' => $o->id,
            'po_number' => $o->po_number,
            'supplier' => $o->supplier?->name,
            'order_status' => $o->order_status,
            'payment_status' => $o->payment_status,
            'total_amount' => Money::format($o->total_amount),
            'paid_amount' => Money::format($o->paid_amount),
            'outstanding' => Money::format($o->outstanding()),
            'items' => $o->items->map(fn ($i) => [
                'product' => $i->product?->name,
                'received_quantity' => $i->received_quantity,
                'quantity' => $i->quantity,
                'cost_per_unit' => Money::format($i->cost_per_unit),
                'total' => Money::format($i->total),
            ])->all(),
            'payments' => $o->payments->map(fn ($p) => [
                'date' => $p->payment_date?->format('Y-m-d'),
                'method' => __('purchasing.methods.'.$p->payment_method),
                'amount' => Money::format($p->amount),
            ])->all(),
        ];
    }
}
