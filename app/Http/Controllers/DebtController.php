<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\Collections\CollectionsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request, CollectionsService $collections): Response
    {
        $search = (string) $request->string('search');
        $onlyOverdue = $request->boolean('overdue');
        $perfFrom = (string) ($request->string('perf_from') ?: now()->startOfMonth()->toDateString());
        $perfTo = (string) ($request->string('perf_to') ?: now()->toDateString());

        $aging = collect($collections->aging());

        if ($search !== '') {
            $aging = $aging->filter(fn ($r) => str_contains(mb_strtolower($r['customer']), mb_strtolower($search)));
        }

        if ($onlyOverdue) {
            $aging = $aging->filter(fn ($r) => $r['oldest_days'] > 30);
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $items = $aging->forPage($page, $perPage)->values()->map(fn ($r) => [
            'customer' => $r['customer'],
            'customer_id' => $r['customer_id'],
            'oldest_days' => $r['oldest_days'],
            'current' => Money::format($r['current']),
            'd30' => Money::format($r['d30']),
            'd60' => Money::format($r['d60']),
            'd90' => Money::format($r['d90']),
            'total' => Money::format($r['total']),
            'total_raw' => (float) $r['total'],
        ]);

        $summary = $collections->summary();
        $performance = $collections->performance($perfFrom, $perfTo);

        return Inertia::render('Debts/Index', [
            'summary' => [
                'total' => Money::format($summary['total']),
                'current' => Money::format($summary['current']),
                'd30' => Money::format($summary['d30']),
                'd60' => Money::format($summary['d60']),
                'd90' => Money::format($summary['d90']),
            ],
            'aging' => new LengthAwarePaginator(
                $items,
                $aging->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'page'],
            ),
            'performance' => [
                'total' => Money::format($performance['total']),
                'count' => $performance['count'],
                'by_branch' => collect($performance['by_branch'])->map(fn ($b) => [
                    'branch' => $b['branch'],
                    'count' => $b['count'],
                    'total' => Money::format($b['total']),
                ])->all(),
            ],
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['value' => $m, 'label' => __('purchasing.methods.'.$m)])->all(),
            'statement' => Inertia::optional(fn () => $this->statementData($request->integer('statement'), $collections)),
            'filters' => [
                'search' => $search,
                'overdue' => $onlyOverdue,
                'perf_from' => $perfFrom,
                'perf_to' => $perfTo,
            ],
            'canManage' => Gate::allows('debts.manage'),
        ]);
    }

    public function collect(Request $request, Customer $customer, CollectionsService $collections): RedirectResponse
    {
        Gate::authorize('debts.manage');

        $data = $request->validate([
            'collect_amount' => 'required|numeric|min:0.01',
            'collect_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'collect_date' => 'required|date',
            'collect_reference' => 'nullable|string|max:100',
        ]);

        try {
            $allocations = $collections->collectFromCustomer($customer->id, [
                'amount' => $data['collect_amount'],
                'payment_method' => $data['collect_method'],
                'payment_date' => $data['collect_date'],
                'reference_number' => ($data['collect_reference'] ?? null) ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('debts.collected', ['count' => count($allocations)]));

        return redirect()->route('debts.index');
    }

    public function remind(SalesInvoice $invoice, CollectionsService $collections): RedirectResponse
    {
        Gate::authorize('debts.manage');

        $collections->markReminded($invoice);

        $this->toastSuccess(__('debts.reminder_logged'));

        return redirect()->back();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function statementData(?int $customerId, CollectionsService $collections): ?array
    {
        if (! $customerId) {
            return null;
        }

        $customer = Customer::find($customerId);

        if (! $customer) {
            return null;
        }

        return [
            'customer_id' => $customer->id,
            'customer' => $customer->name,
            'invoices' => $collections->customerInvoices($customerId)->map(fn (SalesInvoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'days_overdue' => $inv->daysOverdue(),
                'balance' => Money::format($inv->balance),
                'print_url' => route('invoices.print', $inv->id),
                'whatsapp_url' => $this->whatsappLink($inv),
                'last_reminder' => $inv->last_reminder_at?->diffForHumans(),
            ])->all(),
        ];
    }

    protected function whatsappLink(SalesInvoice $invoice): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $invoice->customer?->phone);

        if (! $phone) {
            return null;
        }

        $message = __('debts.reminder_message', [
            'number' => $invoice->invoice_number,
            'amount' => Money::format($invoice->balance),
            'due' => $invoice->due_date?->format('Y-m-d') ?? '—',
        ]);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
