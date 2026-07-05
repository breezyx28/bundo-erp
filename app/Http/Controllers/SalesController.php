<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\SalesDraftRequest;
use App\Http\Requests\SalesInvoiceRequest;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Services\Sales\SalesService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['invoice_number', 'invoice_date', 'net_amount', 'balance', 'payment_status'];

    public function index(Request $request, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $statusFilter = (string) $request->string('status');

        $base = config('money.base');
        $currencyConfig = config("money.currencies.{$base}", ['symbol' => $base, 'decimals' => 2]);

        $query = SalesInvoice::query()
            ->posted()
            ->search($search)
            ->status($statusFilter)
            ->with(['customer:id,name']);
        $this->applyDateRange($query, $request, 'invoice_date');
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Sales/Index', [
            'invoices' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (SalesInvoice $inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer' => $inv->customer?->name,
                    'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                    'net_amount' => Money::format($inv->net_amount),
                    'balance' => Money::format($inv->balance),
                    'balance_raw' => (float) $inv->balance,
                    'payment_status' => $inv->payment_status,
                    'is_overdue' => $inv->isOverdue(),
                ]),
            'customerOptions' => $catalog->customers(),
            'productOptions' => Product::query()->active()->orderBy('name')
                ->get(['id', 'name', 'selling_price'])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float) $p->selling_price,
                ]),
            'stockMap' => ProductBatch::query()
                ->selectRaw('product_id, SUM(quantity) as qty')
                ->groupBy('product_id')
                ->pluck('qty', 'product_id')
                ->map(fn ($q) => (int) $q),
            'statusOptions' => collect(['paid', 'partial', 'unpaid'])
                ->map(fn ($s) => ['value' => $s, 'label' => __('sales.pay.'.$s)])->all(),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['value' => $m, 'label' => __('purchasing.methods.'.$m)])->all(),
            'discountTypeOptions' => [
                ['value' => 'percentage', 'label' => '%'],
                ['value' => 'fixed', 'label' => Money::base()],
            ],
            'currency' => ['symbol' => $currencyConfig['symbol'], 'decimals' => $currencyConfig['decimals']],
            'detail' => Inertia::optional(fn () => $this->detailData($request->integer('detail'))),
            'sortOptions' => [
                ['value' => 'invoice_number', 'label' => __('sales.invoice_number')],
                ['value' => 'invoice_date', 'label' => __('sales.date')],
                ['value' => 'net_amount', 'label' => __('sales.net')],
                ['value' => 'balance', 'label' => __('sales.balance')],
            ],
            'filters' => [
                'search' => $search,
                'status' => $statusFilter ?: null,
                ...$this->tableFilterState($request, $this->sortable),
            ],
            'canCreate' => Gate::allows('invoices.create'),
            'canPay' => Gate::allows('payments.create'),
            'canVoid' => Gate::allows('invoices.delete'),
            'drafts' => SalesInvoice::query()
                ->draft()
                ->with(['customer:id,name', 'items:product_id,quantity,unit_price,sales_invoice_id'])
                ->withCount('items')
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get()
                ->map(fn (SalesInvoice $d) => [
                    'id' => $d->id,
                    'hold_label' => $d->hold_label,
                    'customer' => $d->customer?->name,
                    'item_count' => $d->items_count,
                    'net_amount' => Money::format($d->net_amount),
                    'net_amount_raw' => (float) $d->net_amount,
                    'created_at' => $d->created_at?->format('Y-m-d H:i'),
                    'sale_type' => $d->sale_type,
                    'customer_id' => $d->customer_id,
                    'invoice_date' => $d->invoice_date?->format('Y-m-d'),
                    'due_date' => $d->due_date?->format('Y-m-d'),
                    'discount_type' => $d->discount_type,
                    'discount_value' => (float) $d->discount_value,
                    'exchange_rate' => (float) $d->exchange_rate,
                    'notes' => $d->notes,
                    'items' => $d->items->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                    ])->all(),
                ]),
        ]);
    }

    public function saveDraft(SalesDraftRequest $request, SalesService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $service->saveDraft([
                'id' => $data['id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'sale_type' => $data['sale_type'] ?? SalesInvoice::TYPE_CASH,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'exchange_rate' => (float) ($data['exchange_rate'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'hold_label' => $data['hold_label'] ?? null,
            ], $data['items'] ?? []);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('sales.order_held'));

        return redirect()->route('sales.index');
    }

    public function postDraft(Request $request, SalesInvoice $sale, SalesService $service): RedirectResponse
    {
        Gate::authorize('invoices.create');

        if (! $sale->isDraft()) {
            $this->toastError(__('sales.not_a_draft'));

            return redirect()->back();
        }

        $data = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'sale_type' => 'nullable|in:cash,credit',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            if ($request->filled('items')) {
                $service->saveDraft([
                    'id' => $sale->id,
                    'customer_id' => $data['customer_id'] ?? $sale->customer_id,
                    'invoice_date' => $data['invoice_date'] ?? $sale->invoice_date?->toDateString(),
                    'due_date' => $data['due_date'] ?? $sale->due_date?->toDateString(),
                    'sale_type' => $data['sale_type'] ?? $sale->sale_type,
                    'discount_type' => $data['discount_type'] ?? null,
                    'discount_value' => (float) ($data['discount_value'] ?? 0),
                    'exchange_rate' => (float) ($data['exchange_rate'] ?? $sale->exchange_rate),
                    'notes' => $data['notes'] ?? null,
                    'hold_label' => $sale->hold_label,
                ], $data['items']);
                $sale->refresh();
            }

            $service->postDraft($sale, [
                'payment_method' => $data['payment_method'],
                'paid_amount' => (float) ($data['paid_amount'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('sales.invoice_created'));

        return redirect()->route('sales.index');
    }

    public function discardDraft(SalesInvoice $sale, SalesService $service): RedirectResponse
    {
        Gate::authorize('invoices.create');

        try {
            $service->discardDraft($sale);
            $this->toastSuccess(__('sales.draft_discarded'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('sales.index');
    }

    public function store(SalesInvoiceRequest $request, SalesService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $service->createInvoice([
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'sale_type' => $data['sale_type'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'exchange_rate' => (float) ($data['exchange_rate'] ?? 0),
                'payment_method' => $data['payment_method'],
                'paid_amount' => (float) ($data['paid_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ], $data['items']);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('sales.invoice_created'));

        return redirect()->route('sales.index');
    }

    public function payment(Request $request, SalesInvoice $sale, SalesService $service): RedirectResponse
    {
        Gate::authorize('payments.create');

        $data = $request->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'pay_date' => 'required|date',
            'pay_reference' => 'nullable|string|max:100',
        ]);

        try {
            $service->recordPayment($sale, [
                'amount' => $data['pay_amount'],
                'payment_method' => $data['pay_method'],
                'payment_date' => $data['pay_date'],
                'reference_number' => ($data['pay_reference'] ?? null) ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('sales.payment_recorded'));

        return redirect()->route('sales.index');
    }

    public function void(SalesInvoice $sale, SalesService $service): RedirectResponse
    {
        Gate::authorize('invoices.delete');

        try {
            $service->void($sale->load('items'));
            $this->toastSuccess(__('sales.voided'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('sales.index');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function detailData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $inv = SalesInvoice::query()
            ->with(['customer:id,name,phone', 'items.product:id,name', 'items.variant', 'payments'])
            ->find($id);

        if (! $inv) {
            return null;
        }

        return [
            'id' => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'payment_status' => $inv->payment_status,
            'sale_type' => $inv->sale_type,
            'customer' => $inv->customer?->name,
            'net_amount' => Money::format($inv->net_amount),
            'paid_amount' => Money::format($inv->paid_amount),
            'balance' => Money::format($inv->balance),
            'print_url' => route('invoices.print', $inv->id),
            'pdf_url' => route('invoices.pdf', $inv->id),
            'items' => $inv->items->map(fn ($item) => [
                'product' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'total' => Money::format($item->total),
            ])->all(),
        ];
    }
}
